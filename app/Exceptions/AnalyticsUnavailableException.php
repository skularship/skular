<?php

namespace App\Exceptions;

use App\Enums\AnalyticsFailureReason;
use RuntimeException;
use Throwable;

/**
 * Thrown when a Google Analytics report cannot be loaded; carries the
 * classified reason and an admin-friendly message for the widget.
 */
class AnalyticsUnavailableException extends RuntimeException
{
    /**
     * @param  AnalyticsFailureReason  $reason  Why the report failed.
     * @param  string  $message  Admin-facing message.
     * @param  Throwable|null  $previous  The original exception.
     */
    public function __construct(
        public readonly AnalyticsFailureReason $reason,
        string $message,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
