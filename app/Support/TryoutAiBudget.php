<?php

namespace App\Support;

use App\Services\PracticeSubmissionService;

/**
 * Time budget of the synchronous Try Out AI steps (Writing assessment,
 * Speaking assessment; Speaking is transcribed in the browser). Each step is one provider
 * call per request with no silent retry, so its timeout must fit the web
 * request limit minus the safety margin; the same limits as the practice
 * evaluation (PracticeSubmissionService) apply. Checked by TryoutAiReliabilityTest.
 */
final class TryoutAiBudget
{
    /**
     * Shortest wall-clock limit of a web request in front of PHP.
     */
    public const REQUEST_TIME_LIMIT_SECONDS = PracticeSubmissionService::REQUEST_TIME_LIMIT_SECONDS;

    /**
     * Seconds kept free for everything that is not a provider call.
     */
    public const REQUEST_SAFETY_MARGIN_SECONDS = PracticeSubmissionService::REQUEST_SAFETY_MARGIN_SECONDS;

    /**
     * Lifetime of the per-answer / per-subtest AI lock; equals the request
     * limit so a lock left by a killed request has expired by the time
     * "Nilai ulang" appears (TryoutAttemptSection::STALLED_AFTER_SECONDS).
     */
    public const LOCK_SECONDS = 60;

    /**
     * Give the request enough PHP execution time for one provider call.
     *
     * Only raises a finite max_execution_time below the request limit.
     */
    public static function ensureExecutionTime(): void
    {
        $limit = (int) ini_get('max_execution_time');

        if ($limit === 0 || $limit >= self::REQUEST_TIME_LIMIT_SECONDS || ! function_exists('set_time_limit')) {
            return;
        }

        set_time_limit(self::REQUEST_TIME_LIMIT_SECONDS);
    }
}
