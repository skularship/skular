<?php

namespace App\Contracts;

use App\Models\User;

/**
 * Answers what a mentee may use on the Gratis or Pro plan. The only place that reads config/plans.php.
 */
interface EntitlementServiceInterface
{
    /**
     * Whether the paid features exist at all (the PREMIUM_ENABLED switch).
     */
    public function premiumEnabled(): bool;

    /**
     * Whether the mentee has an active Pro plan.
     */
    public function isPro(User $user): bool;

    /**
     * Whether the mentee may use a boolean feature ("vault" or "checklist").
     */
    public function can(User $user, string $feature): bool;

    /**
     * How many scholarships get deadline reminders, or null for all of them.
     */
    public function reminderLimit(User $user): ?int;
}
