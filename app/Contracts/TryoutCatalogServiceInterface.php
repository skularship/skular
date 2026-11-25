<?php

namespace App\Contracts;

use App\Models\Tryout;
use App\Models\TryoutAttempt;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Lists the Try Outs a mentee sees and decides whether a new attempt may start.
 */
interface TryoutCatalogServiceInterface
{
    /**
     * Published Try Outs with their sections, in admin order.
     *
     * @return Collection<int, Tryout>
     */
    public function published(User $user): Collection;

    /**
     * Whether the mentee may start a new attempt of the Try Out.
     */
    public function canStart(User $user, Tryout $tryout): bool;

    /**
     * Why a new attempt may not start, or null when it may.
     */
    public function blockedReason(User $user, Tryout $tryout): ?string;

    /**
     * The attempt the mentee is still working on, or null.
     */
    public function activeAttempt(User $user, Tryout $tryout): ?TryoutAttempt;

    /**
     * The most recent completed attempt, or null.
     */
    public function latestCompletedAttempt(User $user, Tryout $tryout): ?TryoutAttempt;
}
