<?php

namespace App\Contracts;

use App\Models\PracticeForm;
use App\Models\User;
use App\Models\UserPracticeResponse;

/**
 * Saves practice answers and drives their AI evaluation lifecycle.
 *
 * Answers are stored first (never lost), then evaluated in the queue; a failed
 * evaluation can be retried without resubmitting.
 */
interface PracticeSubmissionServiceInterface
{
    /**
     * Validate and store a practice submission, then queue AI feedback.
     *
     * @param  array<string, mixed>  $answers
     */
    public function submit(User $user, PracticeForm $practice_form, array $answers): UserPracticeResponse;

    /**
     * Evaluate a stored submission from the background job.
     *
     * Skips a submission that is no longer waiting for an evaluation, and a
     * job dispatched for an older submission than the stored one.
     *
     * @param  int|null  $attempt  The `attempts` value the job was dispatched for; null for legacy jobs.
     */
    public function evaluateSubmission(int $response_id, int $practice_form_id, ?int $attempt = null): void;

    /**
     * Queue a new AI evaluation for a stored submission ("Nilai ulang").
     *
     * Returns the response unchanged when it cannot be retried or another
     * evaluation is still running.
     */
    public function retryAiFeedback(UserPracticeResponse $response): UserPracticeResponse;

    /**
     * Determine whether an evaluation of this submission is running right now.
     */
    public function isEvaluationRunning(UserPracticeResponse $response): bool;
}
