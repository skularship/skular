<?php

namespace App\Contracts;

use App\Enums\OrderStatus;
use App\Exceptions\PaymentException;
use App\Models\Order;
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
     * @throws PaymentException When the signature or the amount is wrong.
     */
    public function receive(array $payload): PaymentEvent;

    /**
     * Apply a stored notification to its order (runs inside the queue job).
     *
     * @throws PaymentException When the amount does not match the order.
     */
    public function process(PaymentEvent $event): void;

    /**
     * Settle an old pending order whose notification never came by asking the provider.
     *
     * No transaction at the provider = the mentee never paid, so the order expires.
     * A final status is stored and queued like a notification; a payment still in
     * progress is left alone (the provider will notify when it ends).
     *
     * @return 'expired'|'queued'|'waiting' What happened to the order.
     *
     * @throws PaymentException When the provider cannot be reached or reports a different amount.
     */
    public function reconcile(Order $order): string;

    /**
     * Ask the provider about a pending order right now and apply a final status at once
     * (the "Cek status pembayaran" button). Never expires an order.
     *
     * @return OrderStatus The order status after the check.
     *
     * @throws PaymentException When the provider cannot be reached or reports a different amount.
     */
    public function refresh(Order $order): OrderStatus;
}
