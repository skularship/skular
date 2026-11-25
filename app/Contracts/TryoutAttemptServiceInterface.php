<?php

namespace App\Contracts;

use App\Enums\TryoutMode;
use App\Exceptions\TryoutNotAvailableException;
use App\Models\Tryout;
use App\Models\TryoutAttempt;
use App\Models\TryoutAttemptSection;
use App\Models\User;

/**
 * Lifecycle of a Try Out attempt: start, subtest order, server deadlines,
 * closing expired subtests and completing the attempt.
 */
interface TryoutAttemptServiceInterface
{
    /**
     * Start an attempt (or return the one in progress) and open its first subtest.
     *
     * @throws TryoutNotAvailableException When the mentee may not start this Try Out.
     */
    public function start(User $user, Tryout $tryout, TryoutMode $mode): TryoutAttempt;

    /**
     * The subtest the mentee is on (closing expired ones first), or null when all are closed.
     */
    public function currentSection(TryoutAttempt $attempt): ?TryoutAttemptSection;

    /**
     * Open the current subtest: starts its timer and serves Writing/Speaking prompts.
     */
    public function startSection(TryoutAttempt $attempt): ?TryoutAttemptSection;

    /**
     * Close a subtest (by the mentee or the deadline), score it if objective, and move on.
     */
    public function submitSection(TryoutAttemptSection $attempt_section): void;

    /**
     * Close the subtest when its deadline plus grace has passed.
     *
     * @return bool True when the subtest was closed now.
     */
    public function closeExpired(TryoutAttemptSection $attempt_section): bool;

    /**
     * Mark the attempt completed and compute the overall band when possible.
     */
    public function complete(TryoutAttempt $attempt): void;
}
