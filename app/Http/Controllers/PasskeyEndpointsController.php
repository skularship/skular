<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

/**
 * Advertise the WebAuthn enrolment and management pages.
 *
 * Kept in a controller instead of a route closure so `php artisan route:cache`
 * can serialize every production route.
 */
class PasskeyEndpointsController extends Controller
{
    /**
     * Return the passkey endpoints for relying-party discovery.
     */
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'enroll' => route('security.edit'),
            'manage' => route('security.edit'),
        ]);
    }
}
