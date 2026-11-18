<?php

namespace App\Contracts;

use App\Exceptions\PaymentException;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;

/**
 * Talks to the payment provider (Midtrans Snap). Kept behind an interface so
 * the provider can be swapped and faked in tests.
 */
interface PaymentGatewayInterface
{
    /**
     * Create a Snap payment session for an order and return its token.
     *
     * @throws PaymentException When the provider is not configured or refuses the request.
     */
    public function createSnapToken(Order $order, User $user, Product $product): string;

    /**
     * Check the signature_key of a notification: SHA512(order_id + status_code + gross_amount + server key).
     *
     * @param  array<string, mixed>  $payload  Decoded notification body.
     */
    public function isValidSignature(array $payload): bool;

    /**
     * Ask the provider for the current status of an order (used when a notification never arrived).
     *
     * @return array<string, mixed>|null The status body (same fields as a notification), or null when the provider has no transaction for this order.
     *
     * @throws PaymentException When the provider is not configured or cannot be reached.
     */
    public function transactionStatus(string $order_id): ?array;

    /**
     * Get what the browser needs to open Snap. The server key is never included.
     *
     * @return array{client_key: string, snap_js_url: string}
     */
    public function clientConfig(): array;
}
