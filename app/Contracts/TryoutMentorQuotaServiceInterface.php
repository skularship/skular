<?php

namespace App\Contracts;

use App\Models\TryoutAttempt;
use App\Models\User;

/**
 * Tracks how many Try Out mentor questions a mentee may still send per attempt.
 */
interface TryoutMentorQuotaServiceInterface
{
    /**
     * Configured number of model questions per attempt.
     */
    public function limit(): int;

    /**
     * Questions the mentee may still send for the attempt.
     */
    public function remaining(User $user, TryoutAttempt $attempt): int;

    /**
     * Reserve one question under a row lock before calling the model.
     *
     * @return bool False when the quota is used up.
     */
    public function reserve(User $user, TryoutAttempt $attempt): bool;

    /**
     * Give a reserved question back after the model call failed.
     */
    public function release(User $user, TryoutAttempt $attempt): void;
}
