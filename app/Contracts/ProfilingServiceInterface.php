<?php

namespace App\Contracts;

use App\Models\User;

interface ProfilingServiceInterface
{
    /**
     * Get the ordered profiling steps.
     *
     * @return array<int, string>
     */
    public function steps(): array;

    /**
     * Get the current value of every profiling step, falling back to persisted data.
     *
     * @return array<string, mixed>
     */
    public function values(User $user): array;

    /**
     * Persist a single profiling step in the session.
     */
    public function put(string $step, mixed $value): void;

    /**
     * Get the filled/completed progress of the profiling wizard.
     *
     * @return array{completed: int, total: int, percentage: int}
     */
    public function progress(User $user): array;

    /**
     * Determine whether the profile holds enough data for scholarship matching.
     */
    public function isComplete(User $user): bool;

    /**
     * Persist every profiling step onto the mentee and clear the session draft.
     */
    public function complete(User $user): User;

    /**
     * Discard the session draft.
     */
    public function forget(): void;
}
