<?php

namespace App\Http\Controllers;

use App\Contracts\PaymentWebhookServiceInterface;
use App\Exceptions\PaymentException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Receives Midtrans payment notifications. Access is granted here and nowhere
 * else; the browser callback after paying never activates Pro.
 */
class MidtransWebhookController extends Controller
{
    /**
     * Verify and store the notification, queue its processing, and answer 200 so Midtrans stops retrying.
     */
    public function __invoke(Request $request, PaymentWebhookServiceInterface $webhook): JsonResponse
    {
        /** @var array<string, mixed> $payload */
        $payload = $request->json()->all();

        try {
            $webhook->receive($payload);
        } catch (PaymentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 403);
        }

        return response()->json(['status' => 'ok']);
    }
}
