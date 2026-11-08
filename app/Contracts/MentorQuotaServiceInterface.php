<?php

namespace App\Contracts;

use App\Models\Module;
use App\Models\User;

/**
 * Tracks how many AI Mentor questions a mentee may still send per module.
 */
interface MentorQuotaServiceInterface
{
    /**
     * Get the configured number of model questions per mentee per module.
     */
    public function limit(): int;

    /**
     * Get how many model questions the mentee may still send for the module.
     */
    public function remaining(User $user, Module $module): int;

    /**
     * Reserve one question before calling the model.
     *
     * Returns false when the quota is already used up.
     */
    public function reserve(User $user, Module $module): bool;

    /**
     * Give back a reserved question after the model call failed.
     */
    public function release(User $user, Module $module): void;
}
