<?php

namespace App\Enums;

use Google\ApiCore\ApiException;
use Throwable;

/**
 * Why a Google Analytics Data API report could not be loaded.
 *
 * The admin page shows the matching message instead of a Livewire error
 * screen, and the log carries the stable value under the key `reason`.
 */
enum AnalyticsFailureReason: string
{
    case NotConfigured = 'not_configured';
    case PermissionDenied = 'permission_denied';
    case Timeout = 'timeout';
    case RateLimited = 'rate_limited';
    case Unavailable = 'unavailable';

    /**
     * Classify an exception thrown while calling the GA4 Data API.
     *
     * @param  Throwable  $exception  The caught exception.
     * @return self The matching reason; unknown errors count as Unavailable.
     */
    public static function fromThrowable(Throwable $exception): self
    {
        for ($current = $exception; $current !== null; $current = $current->getPrevious()) {
            if ($current instanceof ApiException) {
                return match ($current->getStatus()) {
                    'PERMISSION_DENIED', 'UNAUTHENTICATED' => self::PermissionDenied,
                    'NOT_FOUND', 'INVALID_ARGUMENT' => self::NotConfigured,
                    'DEADLINE_EXCEEDED' => self::Timeout,
                    'RESOURCE_EXHAUSTED' => self::RateLimited,
                    default => self::Unavailable,
                };
            }

            $message = strtolower($current->getMessage());

            if (str_contains($message, 'timed out') || str_contains($message, 'timeout') || str_contains($message, 'curl error 28')) {
                return self::Timeout;
            }

            // google/auth rejects a missing or malformed service-account JSON with these messages.
            if (str_contains($message, 'credentials') || str_contains($message, 'json key')) {
                return self::NotConfigured;
            }
        }

        return self::Unavailable;
    }

    /**
     * Message for the admin, with the next step to fix it.
     *
     * @param  string|null  $service_account_email  The service account to grant access to, when known.
     * @return string Indonesian message.
     */
    public function message(?string $service_account_email = null): string
    {
        return match ($this) {
            self::NotConfigured => 'Google Analytics belum dikonfigurasi dengan benar. Periksa ANALYTICS_PROPERTY_ID (ID property numerik, bukan G-XXXX) dan file kredensial service account.',
            self::PermissionDenied => 'Service account belum punya akses ke property GA4 ini. Tambahkan '
                .($service_account_email ?? 'email service account')
                .' sebagai Viewer di GA4 → Admin → Property access management.',
            self::Timeout => 'Google Analytics terlalu lama merespons. Coba muat ulang beberapa saat lagi.',
            self::RateLimited => 'Kuota Google Analytics Data API sedang habis. Coba lagi dalam beberapa menit.',
            self::Unavailable => 'Data Google Analytics belum bisa dimuat. Coba muat ulang beberapa saat lagi.',
        };
    }
}
