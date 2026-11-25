<?php

namespace App\Contracts;

use App\Models\TryoutAnswer;
use App\Models\TryoutAttemptSection;

/**
 * Grades Writing tasks of the Try Out with AI, one task per request.
 */
interface WritingAssessmentServiceInterface
{
    /**
     * Grade one Writing task synchronously under a per-answer lock.
     *
     * @return TryoutAnswer Completed or Failed; unchanged when another request holds the lock.
     */
    public function assess(TryoutAnswer $answer): TryoutAnswer;

    /**
     * Grade a failed or stalled task again ("Nilai ulang"), within the retry cap.
     */
    public function retry(TryoutAnswer $answer): TryoutAnswer;

    /**
     * Grade the next task still waiting in a closed Writing subtest.
     *
     * @return bool True when a task was graded in this call.
     */
    public function assessNext(TryoutAttemptSection $attempt_section): bool;

    /**
     * Whether a request currently grades this task.
     */
    public function isRunning(TryoutAnswer $answer): bool;
}
