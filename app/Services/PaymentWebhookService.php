<?php

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use App\Contracts\PaymentWebhookServiceInterface;
use App\Contracts\SubscriptionServiceInterface;
use App\Enums\OrderStatus;
use App\Exceptions\PaymentException;
use App\Jobs\ProcessMidtransNotification;
use App\Models\Order;
use App\Models\PaymentEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Receives a Midtrans notification and applies it to its order through a queue job.
 *
 * Rules: reject a wrong signature or amount, keep every raw payload for audit,
 * never move a paid order, and activate Pro exactly once per paid order (the
 * order row is locked, so a repeated notification finds it already paid).
 */
class PaymentWebhookService implements PaymentWebhookServiceInterface
{
    /**
     * Hours after which a pending order is checked with Midtrans and, when no payment
     * was started, expired. Snap sessions expire after 24 hours; one hour of margin.
     */
    public const EXPIRE_AFTER_HOURS = 25;

    /**
     * Inject the provider (signature check) and the subscription service.
     */
    public function __construct(
        private readonly PaymentGatewayInterface $gateway,
        private readonly SubscriptionServiceInterface $subscriptions,
    ) {}

    /**
     * Verify the signature and amount, store the raw payload, and queue its processing.
     *
     * The raw payload is saved before queueing, so a payment is never lost even when
     * the queue is down; the order still changes only inside the job.
     *
     * @param  array<string, mixed>  $payload  Decoded notification body.
     *
     * @throws PaymentException When the signature or the amount is wrong.
     */
    public function receive(array $payload): PaymentEvent
    {
        if (! $this->gateway->isValidSignature($payload)) {
            Log::warning('Midtrans notification rejected: invalid signature.', ['order_id' => $payload['order_id'] ?? null]);

            throw PaymentException::invalidSignature();
        }

        $order_id = is_string($payload['order_id'] ?? null) ? $payload['order_id'] : null;
        $order = $order_id === null ? null : Order::query()->where('order_id', $order_id)->first();

        if ($order instanceof Order && $this->amountOf($payload) !== $order->amount) {
            Log::warning('Midtrans notification rejected: amount mismatch.', ['order_id' => $order_id]);

            throw PaymentException::amountMismatch();
        }

        return $this->store($order_id, $payload);
    }

    /**
     * Settle an old pending order whose notification never came by asking Midtrans.
     *
     * Only orders older than EXPIRE_AFTER_HOURS are touched, so a Snap session that
     * can still be paid is never expired. The status body comes from an authenticated
     * Midtrans request, so it is stored without a signature check (the amount is
     * still compared). While an earlier status of the order waits in the queue, no
     * second copy is stored.
     *
     * @return 'expired'|'queued'|'waiting' What happened to the order.
     *
     * @throws PaymentException When Midtrans cannot be reached or reports a different amount.
     */
    public function reconcile(Order $order): string
    {
        if ($order->status !== OrderStatus::Pending
            || $order->created_at === null
            || $order->created_at->greaterThan(now()->subHours(self::EXPIRE_AFTER_HOURS))) {
            return 'waiting';
        }

        $payload = $this->gateway->transactionStatus($order->order_id);

        if ($payload === null) {
            return $this->expire($order) ? 'expired' : 'waiting';
        }

        if (($payload['transaction_status'] ?? null) === 'pending') {
            return 'waiting';
        }

        $this->assertStatusAmount($order, $payload);

        $already_waiting = PaymentEvent::query()
            ->where('order_id', $order->order_id)
            ->whereNull('processed_at')
            ->exists();

        if (! $already_waiting) {
            $this->store($order->order_id, $payload);
        }

        return 'queued';
    }

    /**
     * Ask Midtrans about a pending order right now, for the "Cek status pembayaran" button.
     *
     * A final status is stored and applied in this request through process(), so the
     * mentee sees Pro without waiting for the queue. process() locks the order row, so
     * a webhook job applying the same payment at the same time cannot activate Pro twice.
     * An order without a transaction is not expired here; payments:reconcile-pending does that.
     *
     * @return OrderStatus The order status after the check.
     *
     * @throws PaymentException When Midtrans cannot be reached or reports a different amount.
     */
    public function refresh(Order $order): OrderStatus
    {
        if ($order->status !== OrderStatus::Pending) {
            return $order->status;
        }

        $payload = $this->gateway->transactionStatus($order->order_id);

        if ($payload === null || ($payload['transaction_status'] ?? null) === 'pending') {
            return OrderStatus::Pending;
        }

        $this->assertStatusAmount($order, $payload);

        $event = PaymentEvent::query()->create(['order_id' => $order->order_id, 'payload' => $payload]);
        $this->process($event);

        return $order->refresh()->status;
    }

    /**
     * Refuse a Midtrans status whose amount differs from the order.
     *
     * @param  array<string, mixed>  $payload
     *
     * @throws PaymentException When the amounts differ.
     */
    private function assertStatusAmount(Order $order, array $payload): void
    {
        if ($this->amountOf($payload) !== $order->amount) {
            Log::warning('Midtrans status rejected: amount mismatch.', ['order_id' => $order->order_id]);

            throw PaymentException::amountMismatch();
        }
    }

    /**
     * Apply a stored notification to its order inside one transaction (runs in the queue job).
     *
     * The order row is locked, so a repeated notification finds it already paid and
     * Pro is activated exactly once per order.
     *
     * @throws PaymentException When the amount does not match the order.
     */
    public function process(PaymentEvent $event): void
    {
        /** @var array<string, mixed> $payload */
        $payload = $event->payload;
        $order_id = $event->order_id;

        DB::transaction(function () use ($event, $payload, $order_id): void {
            $order = $order_id === null
                ? null
                : Order::query()->where('order_id', $order_id)->lockForUpdate()->first();

            if (! $order instanceof Order) {
                Log::warning('Midtrans notification for an unknown order.', ['order_id' => $order_id]);
            } else {
                if ($this->amountOf($payload) !== $order->amount) {
                    throw PaymentException::amountMismatch();
                }

                if ($order->status !== OrderStatus::Paid) {
                    $this->apply($order, $payload);
                }
            }

            $event->forceFill(['processed_at' => now()])->save();
        });
    }

    /**
     * Keep the raw status for audit and queue its processing.
     *
     * @param  array<string, mixed>  $payload
     */
    private function store(?string $order_id, array $payload): PaymentEvent
    {
        $event = PaymentEvent::query()->create(['order_id' => $order_id, 'payload' => $payload]);

        ProcessMidtransNotification::dispatch($event->id);

        return $event;
    }

    /**
     * Mark a still-pending order expired. The row is locked so a notification that
     * arrives at the same moment wins: a paid or failed order is never changed here.
     */
    private function expire(Order $order): bool
    {
        return DB::transaction(function () use ($order): bool {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->first();

            if (! $locked instanceof Order || $locked->status !== OrderStatus::Pending) {
                return false;
            }

            $locked->status = OrderStatus::Expired;
            $locked->save();

            return true;
        });
    }

    /**
     * Read the gross amount of a notification as whole rupiah.
     *
     * @param  array<string, mixed>  $payload
     */
    private function amountOf(array $payload): int
    {
        return (int) round((float) ($payload['gross_amount'] ?? 0));
    }

    /**
     * Move the order to the status the notification reports and activate Pro when it is paid.
     *
     * @param  array<string, mixed>  $payload
     */
    private function apply(Order $order, array $payload): void
    {
        $transaction_status = is_string($payload['transaction_status'] ?? null) ? $payload['transaction_status'] : '';
        $fraud_status = is_string($payload['fraud_status'] ?? null) ? $payload['fraud_status'] : null;

        $next = match (true) {
            $transaction_status === 'settlement' => OrderStatus::Paid,
            $transaction_status === 'capture' && ($fraud_status === null || $fraud_status === 'accept') => OrderStatus::Paid,
            $transaction_status === 'expire' => OrderStatus::Expired,
            in_array($transaction_status, ['deny', 'cancel', 'failure'], true) => OrderStatus::Failed,
            default => OrderStatus::Pending,
        };

        if (is_string($payload['transaction_id'] ?? null)) {
            $order->midtrans_transaction_id = $payload['transaction_id'];
        }

        if (is_string($payload['payment_type'] ?? null)) {
            $order->payment_type = $payload['payment_type'];
        }

        $order->status = $next;

        if ($next === OrderStatus::Paid) {
            $order->paid_at = now();
            $order->save();

            $this->subscriptions->activate($order->user, $order->product, $order->paid_at);

            return;
        }

        $order->save();
    }
}
