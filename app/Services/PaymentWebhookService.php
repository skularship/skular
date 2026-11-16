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

        $event = PaymentEvent::query()->create(['order_id' => $order_id, 'payload' => $payload]);

        ProcessMidtransNotification::dispatch($event->id);

        return $event;
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
