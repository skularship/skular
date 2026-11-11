<?php

namespace App\Exceptions;

use App\Enums\AiFailureReason;
use RuntimeException;
use Throwable;

/**
 * Thrown when AI feedback generation fails so callers can fall back gracefully.
 *
 * Carries the classified provider failure so the queued evaluation can tell a
 * passing hiccup (timeout, rate limit, empty answer) from a setup problem.
 */
class AiFeedbackException extends RuntimeException
{
    /**
     * @param  string  $message  Friendly Indonesian message shown to the mentee.
     * @param  AiFailureReason|null  $reason  Classified provider failure; null when the provider answered without a usable evaluation.
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        public readonly ?AiFailureReason $reason = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Determine whether asking the provider again may succeed.
     *
     * A missing or invalid configuration fails the same way every time, so
     * only that case is excluded from an automatic retry.
     */
    public function isTransient(): bool
    {
        return $this->reason !== AiFailureReason::Configuration;
    }
}
