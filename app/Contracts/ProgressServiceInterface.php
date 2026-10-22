<?php

namespace App\Contracts;

use App\Data\FaseProgressData;
use App\Data\LearningOverviewData;
use App\Data\ModuleProgressData;
use App\Data\ProgressSummaryData;
use App\Models\Checkpoint;
use App\Models\Module;
use App\Models\Scholarship;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Resolves the progress of a mentee inside their active curriculum.
 *
 * Inti progress is shared across every scholarship because it is keyed by the
 * mentee and the module. Template progress follows the scholarship the module
 * belongs to.
 */
interface ProgressServiceInterface
{
    /**
     * Create any missing checkpoint rows of the current path.
     *
     * The mentee UI derives unlock states without writing rows; this method is
     * for maintenance and admin snapshots.
     */
    public function ensureCheckpoints(User $user): void;

    /**
     * Get the aggregate learning progress for the mentee.
     */
    public function summary(User $user): ProgressSummaryData;

    /**
     * Get the full roadmap grouped by fase.
     *
     * @return array<int, FaseProgressData>
     */
    public function roadmap(User $user): array;

    /**
     * Get the dashboard summary and roadmap with one curriculum read.
     *
     * Passing null resolves the scholarship currently in focus.
     */
    public function overview(User $user, ?Scholarship $scholarship = null): LearningOverviewData;

    /**
     * Get the roadmap entry that follows a module on the active path.
     */
    public function nextModule(User $user, Module $module): ?ModuleProgressData;

    /**
     * Get the checkpoint of a module for the mentee.
     *
     * An unsaved checkpoint with the derived status is returned while the
     * mentee has not touched the module yet.
     */
    public function checkpointFor(User $user, Module $module): ?Checkpoint;

    /**
     * Mark a module as started.
     */
    public function startModule(User $user, Module $module): Checkpoint;

    /**
     * Mark a module as completed and unlock the next one.
     */
    public function completeModule(User $user, Module $module, ?int $score = null): Checkpoint;

    /**
     * Determine whether every mandatory module of a scholarship is completed.
     *
     * Passing null checks the scholarship currently in focus. Library modules
     * never count towards path completion.
     */
    public function isPathCompleted(User $user, ?Scholarship $scholarship = null): bool;

    /**
     * Get the curriculum modules of the mentee's active scholarship.
     *
     * Passing a scholarship resolves that path explicitly (for example the
     * scholarship that is being transitioned away from).
     *
     * @return Collection<int, Module>
     */
    public function curriculum(User $user, ?Scholarship $scholarship = null): Collection;
}
