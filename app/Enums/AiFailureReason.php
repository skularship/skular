<?php

namespace App\Enums;

use App\Exceptions\AiConfigurationException;
use Illuminate\Http\Client\RequestException;
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
     * The provider answered, but without a usable result (empty, not JSON,
     * or missing required fields). Raised by the services, never by fromThrowable().
     */
    case InvalidResponse = 'invalid_response';

    /**
     * HTTP statuses that fail the same way on every retry: a wrong key, a
     * forbidden route or an unknown model id.
     */
    private const CONFIGURATION_STATUSES = [401, 403, 404];

    /**
     * Classify a provider exception.
     *
     * The whole previous-exception chain is inspected because the Laravel AI
     * SDK wraps transport errors: a cURL timeout reaches the caller as
     * ProviderConnectionException("Could not connect to AI provider") with
     * the original ConnectionException as its previous exception.
     */
    public static function fromThrowable(Throwable $exception): self
    {
        $chain = [];

        for ($current = $exception; $current !== null; $current = $current->getPrevious()) {
            $chain[] = $current;
        }

        foreach ($chain as $link) {
            if ($link instanceof AiConfigurationException || $link instanceof InsufficientCreditsException) {
                return self::Configuration;
            }

            if ($link instanceof RateLimitedException || $link instanceof ProviderOverloadedException) {
                return self::RateLimited;
            }

            if ($link instanceof RequestException && $link->response !== null) {
                $status = $link->response->status();

                if (in_array($status, self::CONFIGURATION_STATUSES, true)) {
                    return self::Configuration;
                }

                if ($status === 408) {
                    return self::Timeout;
                }
            }
        }

        foreach ($chain as $link) {
            $message = strtolower($link->getMessage());

            if ($link->getCode() === 28 || str_contains($message, 'curl error 28') || str_contains($message, 'timed out') || str_contains($message, 'timeout')) {
                return self::Timeout;
            }
        }

        return self::Unavailable;
    }
}
