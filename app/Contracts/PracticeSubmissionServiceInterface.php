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
     * Skips a submission that already carries completed feedback.
     */
    public function evaluateSubmission(int $response_id, int $practice_form_id): void;

    /**
     * Request AI feedback again for a stored submission.
     */
    public function retryAiFeedback(UserPracticeResponse $response): UserPracticeResponse;
}
