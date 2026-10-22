<?php

namespace App\Contracts;

use App\Models\PracticeForm;
use App\Models\User;
use App\Models\UserPracticeResponse;

interface PracticeSubmissionServiceInterface
{
    /**
     * Validate and store a practice submission, then request AI feedback.
     *
     * @param  array<string, mixed>  $answers
     */
    public function submit(User $user, PracticeForm $practice_form, array $answers): UserPracticeResponse;

    /**
     * Request AI feedback again for a stored submission.
     */
    public function retryAiFeedback(UserPracticeResponse $response): UserPracticeResponse;
}
