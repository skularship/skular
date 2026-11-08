<?php

namespace App\Enums;

use App\Exceptions\AiConfigurationException;
use Laravel\Ai\Exceptions\InsufficientCreditsException;
use Laravel\Ai\Exceptions\ProviderOverloadedException;
use Laravel\Ai\Exceptions\RateLimitedException;
use Throwable;

/**
 * Why a call to the AI provider failed.
 *
 * Every AI feature classifies provider errors the same way, so the mentee
 * always receives a message that matches what actually happened and the log
 * carries a stable reason that can be counted on a dashboard.
 */
enum AiFailureReason: string
{
    case Configuration = 'configuration';
    case RateLimited = 'rate_limited';
    case Timeout = 'timeout';
    case Unavailable = 'unavailable';

    /**
     * Classify a provider exception.
     */
    public static function fromThrowable(Throwable $exception): self
    {
        if ($exception instanceof AiConfigurationException || $exception instanceof InsufficientCreditsException) {
            return self::Configuration;
        }

        if ($exception instanceof RateLimitedException || $exception instanceof ProviderOverloadedException) {
            return self::RateLimited;
        }

        $message = strtolower($exception->getMessage());

        if ($exception->getCode() === 28 || str_contains($message, 'timed out') || str_contains($message, 'timeout')) {
            return self::Timeout;
        }

        return self::Unavailable;
    }
}
