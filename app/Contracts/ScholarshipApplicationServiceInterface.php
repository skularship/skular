<?php

namespace App\Contracts;

use App\Enums\ApplicationResult;
use App\Models\ScholarshipApplication;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

interface ScholarshipApplicationServiceInterface
{
    /**
     * Store the mentee's scholarship selection and return how many were added.
     *
     * @param  array<int, int>  $scholarship_ids
     */
    public function select(User $user, array $scholarship_ids, ?int $primary_id = null): int;

    /**
     * Get the given scholarship ids that are no longer open for application.
     *
     * @param  array<int, int>  $scholarship_ids
     * @return array<int, int>
     */
    public function unavailableIds(array $scholarship_ids): array;

    /**
     * Count how many scholarships the mentee has already selected.
     */
    public function selectedCount(User $user): int;

    /**
     * Count how many more scholarships the mentee may still add.
     */
    public function remainingSlots(User $user): int;

    /**
     * Determine whether the mentee reached the five scholarship cap.
     */
    public function hasReachedLimit(User $user): bool;

    /**
     * Get every application ordered by the closest registration deadline.
     *
     * @return Collection<int, ScholarshipApplication>
     */
    public function orderedApplications(User $user): Collection;

    /**
     * Get the scholarship whose learning path is currently in focus.
     *
     * Applications that already finished their learning path, or whose result
     * was reported, are skipped so the next queued scholarship becomes active.
     */
    public function activeApplication(User $user): ?ScholarshipApplication;

    /**
     * Resolve the active application from an already loaded ordered collection.
     *
     * @param  Collection<int, ScholarshipApplication>  $applications
     */
    public function activeApplicationFrom(Collection $applications): ?ScholarshipApplication;

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
     * Promote an application to be the mentee's primary scholarship.
     */
    public function setPrimary(User $user, ScholarshipApplication $application): void;

    /**
     * Record the mentee's confirmation that they submitted the application externally.
     */
    public function confirmSubmission(
        User $user,
        ScholarshipApplication $application,
        ?UploadedFile $proof,
        ?string $note,
    ): ScholarshipApplication;

    /**
     * Record the mentee's reported scholarship result.
     */
    public function reportResult(
        User $user,
        ScholarshipApplication $application,
        ApplicationResult $result,
    ): ScholarshipApplication;
}
