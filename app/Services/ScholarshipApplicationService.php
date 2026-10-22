<?php

namespace App\Services;

use App\Contracts\ScholarshipApplicationServiceInterface;
use App\Enums\ApplicationResult;
use App\Enums\ApplicationStatus;
use App\Models\Scholarship;
use App\Models\ScholarshipApplication;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ScholarshipApplicationService implements ScholarshipApplicationServiceInterface
{
    /**
     * Maximum number of scholarships a single mentee may prepare.
     */
    public const MAX_SELECTIONS = 5;

    /**
     * Private disk folder holding submission screenshots.
     */
    private const PROOF_FOLDER = 'submissions';

    /**
     * Store the mentee's scholarship selection and return how many were added.
     *
     * The five scholarship cap is enforced here, inside a transaction with a
     * pessimistic lock, so two near-simultaneous requests can never push a
     * mentee past the limit or create duplicate applications.
     *
     * @param  array<int, int>  $scholarship_ids
     */
    public function select(User $user, array $scholarship_ids, ?int $primary_id = null): int
    {
        $ids = $this->normaliseIds($scholarship_ids);

        if ($ids === []) {
            // The mentee only reordered their existing selection.
            $this->applyPrimary($user, $primary_id);

            return 0;
        }

        // Only scholarships that are still open for registration may be chosen,
        // so a lapsed closing date can never be selected even while the record
        // still carries the "active" status.
        $scholarships = Scholarship::query()
            ->whereIn('id', $ids)
            ->open()
            ->orderBy('closing_date')
            ->get();

        if ($scholarships->isEmpty()) {
            return 0;
        }

        $added = DB::transaction(function () use ($user, $scholarships): int {
            /** @var Collection<int, int> $existing */
            $existing = $user->scholarshipApplications()
                ->lockForUpdate()
                ->pluck('scholarship_id');

            $slots = self::MAX_SELECTIONS - $existing->count();
            $added = 0;

            foreach ($scholarships as $scholarship) {
                if ($slots <= 0) {
                    break;
                }

                if ($existing->contains($scholarship->id)) {
                    continue;
                }

                ScholarshipApplication::query()->create([
                    'user_id' => $user->id,
                    'scholarship_id' => $scholarship->id,
                    'status' => ApplicationStatus::Selected,
                    'is_primary' => false,
                    'selected_at' => now(),
                ]);

                $slots--;
                $added++;
            }

            return $added;
        });

        $this->applyPrimary($user, $primary_id);

        return $added;
    }

    /**
     * Get the given scholarship ids that are no longer open for application.
     *
     * @param  array<int, int>  $scholarship_ids
     * @return array<int, int>
     */
    public function unavailableIds(array $scholarship_ids): array
    {
        $ids = $this->normaliseIds($scholarship_ids);

        if ($ids === []) {
            return [];
        }

        /** @var array<int, int> $available */
        $available = Scholarship::query()
            ->whereIn('id', $ids)
            ->open()
            ->pluck('id')
            ->all();

        return array_values(array_diff($ids, $available));
    }

    /**
     * Count how many scholarships the mentee has already selected.
     */
    public function selectedCount(User $user): int
    {
        return $user->scholarshipApplications()->count();
    }

    /**
     * Count how many more scholarships the mentee may still add.
     */
    public function remainingSlots(User $user): int
    {
        return max(0, self::MAX_SELECTIONS - $this->selectedCount($user));
    }

    /**
     * Determine whether the mentee reached the five scholarship cap.
     */
    public function hasReachedLimit(User $user): bool
    {
        return $this->remainingSlots($user) === 0;
    }

    /**
     * Get every application ordered by the closest registration deadline.
     *
     * Applications without a deadline are pushed to the end and ties fall back
     * to the application id, so the ordering is always deterministic.
     *
     * @return Collection<int, ScholarshipApplication>
     */
    public function orderedApplications(User $user): Collection
    {
        return ScholarshipApplication::query()
            ->select('scholarship_applications.*')
            ->with('scholarship')
            ->where('scholarship_applications.user_id', $user->id)
            ->join('scholarships', 'scholarships.id', '=', 'scholarship_applications.scholarship_id')
            ->orderByRaw('scholarships.closing_date is null')
            ->orderBy('scholarships.closing_date')
            ->orderBy('scholarship_applications.id')
            ->get();
    }

    /**
     * Get the scholarship the mentee is currently preparing.
     *
     * The mentee's explicit primary choice wins while its cycle is still open
     * and its learning path has not been finished yet. Otherwise the nearest
     * deadline application still awaiting a result becomes the active one.
     */
    public function activeApplication(User $user): ?ScholarshipApplication
    {
        return $this->activeApplicationFrom($this->orderedApplications($user));
    }

    /**
     * Resolve the active application from an already loaded ordered collection.
     *
     * The dashboard loads the queue once and uses this method, so the active
     * scholarship never costs a second identical query.
     *
     * @param  Collection<int, ScholarshipApplication>  $applications
     */
    public function activeApplicationFrom(Collection $applications): ?ScholarshipApplication
    {
        $primary = $applications->firstWhere('is_primary', true);

        if ($primary instanceof ScholarshipApplication
            && $primary->status !== ApplicationStatus::ResultReported
            && ! $primary->hasCompletedLearning()) {
            return $primary;
        }

        return $applications->first(
            fn (ScholarshipApplication $application): bool => $application->status !== ApplicationStatus::ResultReported
                && ! $application->hasCompletedLearning(),
        );
    }

    /**
     * Get the scholarship queued right after the active one.
     */
    public function nextApplication(User $user): ?ScholarshipApplication
    {
        $applications = $this->orderedApplications($user);

        return $this->nextApplicationFrom($applications, $this->activeApplicationFrom($applications));
    }

    /**
     * Resolve the next application from an already loaded ordered collection.
     *
     * @param  Collection<int, ScholarshipApplication>  $applications
     */
    public function nextApplicationFrom(
        Collection $applications,
        ?ScholarshipApplication $active = null,
    ): ?ScholarshipApplication {
        $active_id = $active?->id;

        return $applications->first(
            fn (ScholarshipApplication $application): bool => $application->id !== $active_id
                && $application->status !== ApplicationStatus::ResultReported
                && ! $application->hasCompletedLearning(),
        );
    }

    /**
     * Promote an application to be the mentee's primary scholarship.
     */
    public function setPrimary(User $user, ScholarshipApplication $application): void
    {
        $this->authorizeOwnership($user, $application);

        DB::transaction(function () use ($user, $application): void {
            $user->scholarshipApplications()->update(['is_primary' => false]);
            $application->forceFill(['is_primary' => true])->save();
        });
    }

    /**
     * Record the mentee's confirmation that they submitted the application externally.
     */
    public function confirmSubmission(
        User $user,
        ScholarshipApplication $application,
        ?UploadedFile $proof,
        ?string $note,
    ): ScholarshipApplication {
        $this->authorizeOwnership($user, $application);

        $path = null;

        if ($proof !== null) {
            $stored = $proof->store(self::PROOF_FOLDER.'/'.$user->id, 'local');

            if (is_string($stored)) {
                $path = $stored;
            }
        }

        if ($path !== null && $application->hasSubmissionProof()) {
            Storage::disk('local')->delete((string) $application->submission_proof);
        }

        $application->fill([
            'status' => ApplicationStatus::Submitted,
            'submitted_at' => now(),
            'submission_confirmed_at' => now(),
            'submission_note' => $note,
        ]);

        if ($path !== null) {
            $application->submission_proof = $path;
        }

        $application->save();

        return $application;
    }

    /**
     * Record the mentee's reported scholarship result and advance the queue.
     *
     * Reporting the same result twice is idempotent: the transition only runs
     * when the application has not already captured that result, so the next
     * scholarship is never skipped by a duplicated submit.
     */
    public function reportResult(
        User $user,
        ScholarshipApplication $application,
        ApplicationResult $result,
    ): ScholarshipApplication {
        $this->authorizeOwnership($user, $application);

        // The mentee must confirm the external submission before a result can be
        // reported, so an accepted result can never be claimed from a selection
        // that was never submitted.
        if (! in_array($application->status, [ApplicationStatus::Submitted, ApplicationStatus::ResultReported], true)) {
            throw ValidationException::withMessages([
                'result' => 'Konfirmasi dulu bahwa kamu sudah mendaftar di website beasiswa sebelum melaporkan hasilnya.',
            ]);
        }

        $already_reported = $application->status === ApplicationStatus::ResultReported
            && $application->result === $result;

        if ($already_reported) {
            return $application;
        }

        $application->fill([
            'status' => ApplicationStatus::ResultReported,
            'result' => $result,
            'result_reported_at' => now(),
        ])->save();

        $this->advancePrimary($user);

        return $application;
    }

    /**
     * Move the primary flag to the next scholarship once the active cycle ends.
     */
    private function advancePrimary(User $user): void
    {
        $ordered = $this->orderedApplications($user);

        $primary = $ordered->firstWhere('is_primary', true);

        // Keep the finished application as the primary when nothing else is
        // left to prepare, so the result and its twibbon stay reachable.
        if ($primary instanceof ScholarshipApplication
            && $primary->status !== ApplicationStatus::ResultReported
            && ! $primary->hasCompletedLearning()) {
            return;
        }

        $next = $ordered->first(
            fn (ScholarshipApplication $application): bool => $application->status !== ApplicationStatus::ResultReported
                && ! $application->hasCompletedLearning(),
        );

        if (! $next instanceof ScholarshipApplication) {
            return;
        }

        $this->setPrimary($user, $next);
    }

    /**
     * Cast, de-duplicate and re-index the given scholarship ids.
     *
     * @param  array<int, int>  $scholarship_ids
     * @return array<int, int>
     */
    private function normaliseIds(array $scholarship_ids): array
    {
        return array_values(array_unique(array_map(
            fn (mixed $id): int => (int) $id,
            $scholarship_ids,
        )));
    }

    /**
     * Resolve and promote the primary application.
     */
    private function applyPrimary(User $user, ?int $primary_id): void
    {
        $primary = $primary_id === null
            ? null
            : $user->scholarshipApplications()->where('scholarship_id', $primary_id)->first();

        $primary ??= $this->defaultPrimary($user);

        if ($primary instanceof ScholarshipApplication) {
            $this->setPrimary($user, $primary);
        }
    }

    /**
     * Pick the application that should lead the queue by default.
     *
     * The closest deadline among the applications still awaiting a result wins;
     * ties and deadline-less records fall back to the application id.
     */
    private function defaultPrimary(User $user): ?ScholarshipApplication
    {
        $ordered = $this->orderedApplications($user);

        return $ordered->first(
            fn (ScholarshipApplication $application): bool => $application->status !== ApplicationStatus::ResultReported
                && ! $application->hasCompletedLearning(),
        ) ?? $ordered->first();
    }

    /**
     * Ensure the application belongs to the given mentee.
     *
     * @throws AuthorizationException
     */
    private function authorizeOwnership(User $user, ScholarshipApplication $application): void
    {
        if ($application->user_id !== $user->id) {
            throw new AuthorizationException('Anda tidak berhak mengubah data ini.');
        }
    }
}
