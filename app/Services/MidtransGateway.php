<?php

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use App\Exceptions\PaymentException;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Midtrans Snap over the plain HTTP API (no extra package). Uses Sandbox until
 * MIDTRANS_IS_PRODUCTION is true. The server key is only read here.
 */
class MidtransGateway implements PaymentGatewayInterface
{
    /**
     * Create a Snap payment session for an order and return its token.
     *
     * @throws PaymentException When the provider is not configured or refuses the request.
     */
    public function createSnapToken(Order $order, User $user, Product $product): string
    {
        $server_key = $this->serverKey();

        if ($server_key === '' || $this->clientConfig()['client_key'] === '') {
            Log::error('Midtrans server key or client key is not configured.');

            throw PaymentException::gatewayUnavailable();
        }

        try {
            $response = Http::withBasicAuth($server_key, '')
                ->acceptJson()
                ->asJson()
                ->timeout(15)
                ->post($this->snapUrl(), [
                    'transaction_details' => [
                        'order_id' => $order->order_id,
                        'gross_amount' => $order->amount,
                    ],
                    'item_details' => [[
                        'id' => $product->code,
                        'price' => $order->amount,
                        'quantity' => 1,
                        'name' => Str::limit($product->name, 50, ''),
                    ]],
                    'customer_details' => [
                        'first_name' => Str::limit($user->name, 100, ''),
                        'email' => $user->email,
                    ],
                    'callbacks' => [
                        'finish' => route('payment.status', $order),
                    ],
                ]);
        } catch (Throwable $exception) {
            Log::error('Midtrans Snap request failed.', ['message' => $exception->getMessage()]);

            throw PaymentException::gatewayUnavailable();
        }

        $token = $response->json('token');

        if (! $response->successful() || ! is_string($token) || $token === '') {
            Log::error('Midtrans Snap refused the request.', ['status' => $response->status(), 'body' => $response->body()]);

            throw PaymentException::gatewayUnavailable();
        }

        return $token;
    }

    /**
     * Check the signature_key of a notification: SHA512(order_id + status_code + gross_amount + server key).
     *
     * @param  array<string, mixed>  $payload  Decoded notification body.
     */
    public function isValidSignature(array $payload): bool
    {
        $server_key = $this->serverKey();
        $signature = $payload['signature_key'] ?? null;

        if ($server_key === '' || ! is_string($signature)) {
            return false;
        }

        $expected = hash('sha512', $this->stringValue($payload, 'order_id')
            .$this->stringValue($payload, 'status_code')
            .$this->stringValue($payload, 'gross_amount')
            .$server_key);

        return hash_equals($expected, $signature);
    }

    /**
     * Ask Midtrans for the status of an order (GET /v2/{order_id}/status).
     *
     * Midtrans answers 404 when no payment was ever started for the order, for
     * example when the mentee closed Snap without choosing a payment method.
     *
     * @return array<string, mixed>|null The status body, or null when Midtrans has no transaction for this order.
     *
     * @throws PaymentException When the server key is missing or Midtrans cannot be reached.
     */
    public function transactionStatus(string $order_id): ?array
    {
        $server_key = $this->serverKey();

        if ($server_key === '') {
            Log::error('Midtrans server key is not configured.');

            throw PaymentException::gatewayUnavailable();
        }

        try {
            $response = Http::withBasicAuth($server_key, '')
                ->acceptJson()
                ->timeout(15)
                ->get($this->apiUrl().'/v2/'.rawurlencode($order_id).'/status');
        } catch (Throwable $exception) {
            Log::error('Midtrans status request failed.', ['order_id' => $order_id, 'message' => $exception->getMessage()]);

            throw PaymentException::gatewayUnavailable();
        }

        // Midtrans may answer a missing transaction with HTTP 404 or with HTTP 200 and status_code "404".
        if ($response->status() === 404 || (string) $response->json('status_code') === '404') {
            return null;
        }

        $body = $response->json();

        if (! $response->successful() || ! is_array($body) || ! is_string($body['transaction_status'] ?? null)) {
            Log::error('Midtrans status request refused.', ['order_id' => $order_id, 'status' => $response->status()]);

            throw PaymentException::gatewayUnavailable();
        }

        /** @var array<string, mixed> $body */
        return $body;
    }

    /**
     * Get what the browser needs to open Snap. The server key is never included.
     *
     * @return array{client_key: string, snap_js_url: string}
     */
    public function clientConfig(): array
    {
        $client_key = config('services.midtrans.client_key');

        return [
            'client_key' => is_string($client_key) ? $client_key : '',
            'snap_js_url' => $this->isProduction()
                ? 'https://app.midtrans.com/snap/snap.js'
                : 'https://app.sandbox.midtrans.com/snap/snap.js',
        ];
    }

    /**
     * Get the Snap transactions endpoint for the current environment.
     */
    private function snapUrl(): string
    {
        return $this->isProduction()
            ? 'https://app.midtrans.com/snap/v1/transactions'
            : 'https://app.sandbox.midtrans.com/snap/v1/transactions';
    }

    /**
     * Get the Core API base URL (transaction status) for the current environment.
     */
    private function apiUrl(): string
    {
        return $this->isProduction()
            ? 'https://api.midtrans.com'
            : 'https://api.sandbox.midtrans.com';
    }

    /**
     * Determine whether production keys are in use.
     */
    private function isProduction(): bool
    {
        return (bool) config('services.midtrans.is_production', false);
    }

    /**
     * Get the server key, or an empty string when it is not set.
     */
    private function serverKey(): string
    {
        $key = config('services.midtrans.server_key');

        return is_string($key) ? $key : '';
    }

    /**
     * Read a payload field exactly as Midtrans sent it (the signature depends on the raw text).
     *
     * @param  array<string, mixed>  $payload
     */
    private function stringValue(array $payload, string $key): string
    {
        $value = $payload[$key] ?? '';

        return is_scalar($value) ? (string) $value : '';
    }
}
