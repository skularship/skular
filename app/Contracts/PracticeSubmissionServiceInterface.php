<?php

namespace App\Contracts;

use App\Models\PracticeForm;
use App\Models\User;
use App\Models\UserPracticeResponse;
use Illuminate\Validation\ValidationException;

/**
 * Saves practice answers and drives their AI evaluation lifecycle.
 *
 * Answers are stored first (never lost), then evaluated synchronously in the
 * same request; a failed evaluation can be retried without resubmitting.
 */
interface PracticeSubmissionServiceInterface
{
    /**
     * Validate and store a practice submission, then evaluate it with AI in the same request.
     *
     * The returned response is normally Completed or Failed. It stays
     * Processing only when another request already holds the evaluation lock
     * of this submission; that request grades the newest answers.
     *
     * @param  array<string, mixed>  $answers
     * @return UserPracticeResponse The stored submission with its evaluation outcome.
     *
     * @throws ValidationException When the answers do not match the form schema.
     */
    public function submit(User $user, PracticeForm $practice_form, array $answers): UserPracticeResponse;

    /**
     * Evaluate a stored submission again ("Nilai ulang") on the same synchronous path as the first evaluation.
     *
     * Returns the response unchanged when it cannot be retried (not failed and
     * not stalled) or another request still holds the evaluation lock.
     *
     * @return UserPracticeResponse The submission with its new (or unchanged) evaluation outcome.
     */
    public function retryAiFeedback(UserPracticeResponse $response): UserPracticeResponse;

    /**
     * Determine whether an evaluation of this submission is running right now.
     *
     * @return bool True while another request holds the evaluation lock.
     */
    public function isEvaluationRunning(UserPracticeResponse $response): bool;
}
