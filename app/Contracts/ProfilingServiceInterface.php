<?php

namespace App\Contracts;

use App\Models\User;

/**
 * Reads and persists the mentee profile collected by the profiling wizard.
 */
interface ProfilingServiceInterface
{
    /**
     * Get the ordered profiling steps.
     *
     * @return array<int, string>
     */
    public function steps(): array;

    /**
     * Get the persisted value of every profiling step.
     *
     * @return array<string, mixed>
     */
    public function values(User $user): array;

    /**
     * Get the filled/completed progress of the persisted profile.
     *
     * @return array{completed: int, total: int, percentage: int}
     */
    public function progress(User $user): array;

    /**
     * Determine whether the profile holds enough data for scholarship matching.
     */
    public function isComplete(User $user): bool;

    /**
     * Validate a whole profiling batch and persist it as one transaction.
     *
     * @param  array<string, mixed>  $answers
     */
    public function complete(User $user, array $answers): User;
}
