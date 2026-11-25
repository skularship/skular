<?php

namespace App\Contracts;

use App\Data\TryoutResultData;
use App\Data\TryoutReviewItemData;
use App\Exceptions\TryoutReviewLockedException;
use App\Models\TryoutAttempt;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Builds the free result of an attempt and the Pro review (key + explanation).
 */
interface TryoutResultServiceInterface
{
    /**
     * The free result: bands, raw scores, final levels and W/S criteria.
     */
    public function result(TryoutAttempt $attempt, User $viewer): TryoutResultData;

    /**
     * Every answered Listening/Reading question with key and explanation (Pro only).
     *
     * @return Collection<int, TryoutReviewItemData>
     *
     * @throws TryoutReviewLockedException When the mentee is not on Pro.
     */
    public function review(User $user, TryoutAttempt $attempt): Collection;
}
