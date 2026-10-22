<?php

namespace App\Contracts;

use App\Models\ScholarshipApplication;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Owns the ordered scholarship queue and the learning-path transition.
 *
 * The transition is driven by learning completion only. Application results
 * (passed/failed) stay a separate concern and never gate the next path.
 */
interface ScholarshipPathServiceInterface
{
    /**
     * Get the applications whose learning path is still open, nearest deadline first.
     *
     * @return Collection<int, ScholarshipApplication>
     */
    public function queue(User $user): Collection;

    /**
     * Get the scholarship queued right after the active one.
     */
    public function nextApplication(User $user): ?ScholarshipApplication;

    /**
     * Resolve the next application from an already loaded ordered collection.
     *
     * @param  Collection<int, ScholarshipApplication>  $applications
     */
    public function nextApplicationFrom(
        Collection $applications,
        ?ScholarshipApplication $active = null,
    ): ?ScholarshipApplication;

    /**
     * Determine whether the active scholarship path is finished.
     */
    public function isActivePathCompleted(User $user): bool;

    /**
     * Finish the active path and open the next queued scholarship.
     *
     * The expected application id makes the operation idempotent: repeating a
     * transition that already happened returns the current active application
     * without touching any data.
     */
    public function advance(User $user, ?int $expected_application_id = null): ?ScholarshipApplication;

    /**
     * Get the paths this mentee has already finished, newest first.
     *
     * @return Collection<int, ScholarshipApplication>
     */
    public function history(User $user): Collection;

    /**
     * Resolve the finished paths from an already loaded ordered collection.
     *
     * @param  Collection<int, ScholarshipApplication>  $applications
     * @return Collection<int, ScholarshipApplication>
     */
    public function historyFrom(Collection $applications): Collection;
}
