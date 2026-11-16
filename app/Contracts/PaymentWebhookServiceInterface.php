<?php

namespace App\Contracts;

use App\Models\PaymentEvent;

/**
 * Handles Midtrans payment notifications. The only place that may activate Pro.
 *
 * Work is split in two so the HTTP answer stays fast and a failure never loses a payment:
 * receive() checks and stores the notification, then queues process() to apply it.
 */
interface PaymentWebhookServiceInterface
{
    /**
     * Verify the signature and amount, store the raw payload, and queue its processing.
     *
     * @param  array<string, mixed>  $payload  Decoded notification body.
     *
     * @throws \App\Exceptions\PaymentException When the signature or the amount is wrong.
     */
    public function receive(array $payload): PaymentEvent;

    /**
     * Apply a stored notification to its order (runs inside the queue job).
     *
     * @throws \App\Exceptions\PaymentException When the amount does not match the order.
     */
    public function process(PaymentEvent $event): void;
}
