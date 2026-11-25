<?php

namespace App\Contracts;

use App\Data\IqResultData;
use App\Data\IqReviewItemData;
use App\Exceptions\IqLockedException;
use App\Models\IqAttempt;
use App\Models\User;

/**
 * Builds what a mentee sees after a Tes IQ: the answered count for
 * everyone, and the scores, category, key and explanation only with paid
 * access (EntitlementServiceInterface::can($user, 'iq_premium')).
 */
interface IqResultServiceInterface
{
    /**
     * Whether the mentee has paid access to the Tes IQ.
     */
    public function canSeeResults(User $user): bool;

    /**
     * Result of a closed attempt; score fields stay null without paid access.
     */
    public function result(IqAttempt $attempt, User $user): IqResultData;

    /**
     * Every question of the attempt with the mentee's choice, key and explanation.
     *
     * @return array<int, IqReviewItemData>
     *
     * @throws IqLockedException Without paid access.
     */
    public function review(IqAttempt $attempt, User $user): array;
}
