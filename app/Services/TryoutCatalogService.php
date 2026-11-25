<?php

namespace App\Services;

use App\Contracts\EntitlementServiceInterface;
use App\Contracts\TryoutCatalogServiceInterface;
use App\Enums\TryoutAttemptStatus;
use App\Models\Tryout;
use App\Models\TryoutAttempt;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Try Out list and start rules: published only, Pro-only Try Outs for Pro,
 * and the attempt limit per Try Out from config/plans.php (Gratis: 1).
 */
class TryoutCatalogService implements TryoutCatalogServiceInterface
{
    /**
     * @param  EntitlementServiceInterface  $entitlements  Plan rules.
     */
    public function __construct(
        private readonly EntitlementServiceInterface $entitlements,
    ) {}

    /**
     * Published Try Outs with their sections, in admin order.
     *
     * @return Collection<int, Tryout>
     */
    public function published(User $user): Collection
    {
        return Tryout::query()
            ->published()
            ->with('sections')
            ->orderBy('ordering')
            ->orderBy('id')
            ->get()
            ->filter(fn (Tryout $tryout): bool => $tryout->sections->isNotEmpty())
            ->values()
            ->toBase();
    }

    /**
     * Whether the mentee may start a new attempt of the Try Out.
     */
    public function canStart(User $user, Tryout $tryout): bool
    {
        return $this->blockedReason($user, $tryout) === null;
    }

    /**
     * Why a new attempt may not start, or null when it may.
     */
    public function blockedReason(User $user, Tryout $tryout): ?string
    {
        if (! $tryout->status->isPublished() || $tryout->sections()->doesntExist()) {
            return 'Try Out ini belum tersedia.';
        }

        if ($tryout->requires_pro && ! $this->entitlements->isPro($user)) {
            return 'Try Out ini khusus paket Pro.';
        }

        $limit = $this->entitlements->tryoutAttemptLimit($user);

        if ($limit !== null) {
            $used = $user->tryoutAttempts()->where('tryout_id', $tryout->id)->count();

            if ($used >= $limit) {
                return 'Kamu sudah memakai jatah '.$limit.' kali mengerjakan Try Out ini. Dengan paket Pro kamu bisa mengulang.';
            }
        }

        return null;
    }

    /**
     * The attempt the mentee is still working on, or null.
     */
    public function activeAttempt(User $user, Tryout $tryout): ?TryoutAttempt
    {
        return $user->tryoutAttempts()
            ->where('tryout_id', $tryout->id)
            ->where('status', TryoutAttemptStatus::InProgress->value)
            ->latest('id')
            ->first();
    }

    /**
     * The most recent completed attempt, or null.
     */
    public function latestCompletedAttempt(User $user, Tryout $tryout): ?TryoutAttempt
    {
        return $user->tryoutAttempts()
            ->where('tryout_id', $tryout->id)
            ->where('status', TryoutAttemptStatus::Completed->value)
            ->latest('id')
            ->first();
    }
}
