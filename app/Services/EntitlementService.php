<?php

namespace App\Services;

use App\Contracts\EntitlementServiceInterface;
use App\Models\User;

/**
 * Reads the plan rules for a mentee. Pro that has ended counts as Gratis.
 */
class EntitlementService implements EntitlementServiceInterface
{
    /**
     * Whether the paid features exist at all (the PREMIUM_ENABLED switch).
     */
    public function premiumEnabled(): bool
    {
        return (bool) config('pricing.enabled');
    }

    /**
     * Whether the mentee has an active Pro plan.
     */
    public function isPro(User $user): bool
    {
        return $this->premiumEnabled() && $user->hasPro();
    }

    /**
     * Whether the mentee may use a boolean feature ("vault" or "checklist").
     */
    public function can(User $user, string $feature): bool
    {
        return (bool) config('plans.'.$this->plan($user).'.'.$feature, false);
    }

    /**
     * How many scholarships get deadline reminders, or null for all of them.
     */
    public function reminderLimit(User $user): ?int
    {
        $limit = config('plans.'.$this->plan($user).'.reminder_applications');

        return is_int($limit) ? $limit : null;
    }

    /**
     * Name of the plan the mentee is on right now.
     */
    private function plan(User $user): string
    {
        return $this->isPro($user) ? 'pro' : 'free';
    }
}
