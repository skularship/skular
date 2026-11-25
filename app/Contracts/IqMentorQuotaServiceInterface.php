<?php

namespace App\Contracts;

use App\Models\User;

/**
 * Tracks how many Tes IQ mentor questions a mentee may still send today
 * (per account per day, Asia/Jakarta).
 */
interface IqMentorQuotaServiceInterface
{
    /**
     * Configured number of model questions per day.
     */
    public function limit(): int;

    /**
     * Questions the mentee may still send today.
     */
    public function remaining(User $user): int;

    /**
     * Reserve one question under a row lock before calling the model.
     *
     * @return bool False when today's quota is used up.
     */
    public function reserve(User $user): bool;

    /**
     * Give a reserved question back after the model call failed.
     */
    public function release(User $user): void;
}
