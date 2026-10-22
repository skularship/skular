<?php

namespace App\Services;

use App\Contracts\ProgressServiceInterface;
use App\Contracts\ScholarshipApplicationServiceInterface;
use App\Contracts\ScholarshipPathServiceInterface;
use App\Enums\ApplicationStatus;
use App\Models\ScholarshipApplication;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ScholarshipPathService implements ScholarshipPathServiceInterface
{
    public function __construct(
        private readonly ScholarshipApplicationServiceInterface $applications,
        private readonly ProgressServiceInterface $progress,
    ) {}

    /**
     * Get the applications whose learning path is still open.
     *
     * Result-reported applications are already handled by the application
     * workflow, so they never reappear as learning targets.
     *
     * @return Collection<int, ScholarshipApplication>
     */
    public function queue(User $user): Collection
    {
        return $this->applications->orderedApplications($user)
            ->filter(fn (ScholarshipApplication $application): bool => ! $application->hasCompletedLearning()
                && $application->status !== ApplicationStatus::ResultReported)
            ->values();
    }

    /**
     * Get the scholarship queued right after the active one.
     */
    public function nextApplication(User $user): ?ScholarshipApplication
    {
        $applications = $this->applications->orderedApplications($user);

        return $this->nextApplicationFrom($applications, $this->applications->activeApplicationFrom($applications));
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
        return $this->applications->nextApplicationFrom($applications, $active);
    }

    /**
     * Determine whether the active scholarship path is finished.
     */
    public function isActivePathCompleted(User $user): bool
    {
        return $this->progress->isPathCompleted($user);
    }

    /**
     * Finish the active path and open the next queued scholarship.
     *
     * Everything happens in one transaction behind a row lock on the mentee, so
     * two simultaneous requests can never open two paths or lose history.
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function advance(User $user, ?int $expected_application_id = null): ?ScholarshipApplication
    {
        return DB::transaction(function () use ($user, $expected_application_id): ?ScholarshipApplication {
            // Serialise concurrent transitions for this mentee.
            User::query()->whereKey($user->getKey())->lockForUpdate()->first();

            $applications = $this->applications->orderedApplications($user);

            if ($expected_application_id !== null) {
                $expected = $applications->firstWhere('id', $expected_application_id);

                if ($expected instanceof ScholarshipApplication) {
                    if ($expected->user_id !== $user->id) {
                        throw new AuthorizationException('Anda tidak berhak mengubah data ini.');
                    }

                    // A repeated request for a path that already left the queue
                    // is a no-op, never a second transition.
                    if ($expected->hasCompletedLearning()) {
                        return $this->applications->activeApplication($user);
                    }
                }
            }

            $active = $this->applications->activeApplication($user);

            if (! $active instanceof ScholarshipApplication) {
                return null;
            }

            if ($expected_application_id !== null && $active->id !== $expected_application_id) {
                throw new AuthorizationException('Jalur belajar ini sudah tidak aktif.');
            }

            if (! $this->progress->isPathCompleted($user, $active->scholarship)) {
                throw ValidationException::withMessages([
                    'path' => 'Selesaikan semua modul wajib beasiswa ini dulu sebelum lanjut ke beasiswa berikutnya.',
                ]);
            }

            $active->forceFill([
                'learning_completed_at' => now(),
                'is_primary' => false,
            ])->save();

            // Resolve the successor from the open queue instead of re-reading
            // the active application, which has just left that queue.
            $next = $this->queue($user)->first(
                fn (ScholarshipApplication $application): bool => $application->id !== $active->id,
            );

            if ($next instanceof ScholarshipApplication) {
                $this->applications->setPrimary($user, $next);

                return $next;
            }

            // Keep the finished application as the primary when nothing is left,
            // so its result and completed roadmap stay reachable.
            $active->forceFill(['is_primary' => true])->save();

            return null;
        });
    }

    /**
     * Get the paths this mentee has already finished, newest first.
     *
     * @return Collection<int, ScholarshipApplication>
     */
    public function history(User $user): Collection
    {
        return $this->historyFrom($this->applications->orderedApplications($user));
    }

    /**
     * Resolve the finished paths from an already loaded ordered collection.
     *
     * @param  Collection<int, ScholarshipApplication>  $applications
     * @return Collection<int, ScholarshipApplication>
     */
    public function historyFrom(Collection $applications): Collection
    {
        return $applications
            ->filter(fn (ScholarshipApplication $application): bool => $application->hasCompletedLearning())
            ->sortByDesc(fn (ScholarshipApplication $application): int => $application->learning_completed_at?->getTimestamp() ?? 0)
            ->values();
    }
}
