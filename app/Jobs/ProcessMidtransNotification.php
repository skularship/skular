<?php

namespace App\Jobs;

use App\Contracts\PaymentWebhookServiceInterface;
use App\Exceptions\PaymentException;
use App\Models\PaymentEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;

/**
 * Applies one stored Midtrans notification to its order and activates Pro when paid.
 *
 * Runs on the "payments" queue. Notifications for the same order never run at the
 * same time, and a retry is safe because a paid order is never changed again.
 */
class ProcessMidtransNotification implements ShouldQueue
{
    use Queueable;

    /**
     * Attempts before the job is parked in failed_jobs.
     */
    public int $tries = 5;

    /**
     * Seconds to wait before each retry.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 30, 120, 600];
    }

    /**
     * Keep the job's payment event id and send it to the payments queue.
     */
    public function __construct(public readonly int $paymentEventId)
    {
        $this->onQueue('payments');
        $this->afterCommit();
    }

    /**
     * Never process two notifications of the same order at once.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        $order_id = PaymentEvent::query()->whereKey($this->paymentEventId)->value('order_id');

        return [(new WithoutOverlapping((string) ($order_id ?? $this->paymentEventId)))->releaseAfter(15)->expireAfter(120)];
    }

    /**
     * Apply the notification. A wrong amount is final and is not retried.
     */
    public function handle(PaymentWebhookServiceInterface $webhook): void
    {
        $event = PaymentEvent::query()->find($this->paymentEventId);

        if (! $event instanceof PaymentEvent || $event->processed_at !== null) {
            return;
        }

        try {
            $webhook->process($event);
        } catch (PaymentException $exception) {
            Log::error('Midtrans notification could not be applied.', ['event' => $event->id, 'reason' => $exception->getMessage()]);

            $this->fail($exception);
        }
    }
}
