<?php

namespace App\Services;

use App\Contracts\LearningPathServiceInterface;
use App\Contracts\ProgressServiceInterface;
use App\Data\FaseProgressData;
use App\Data\LearningOverviewData;
use App\Data\ModuleProgressData;
use App\Data\ProgressSummaryData;
use App\Enums\CheckpointStatus;
use App\Enums\ModuleCategory;
use App\Models\Checkpoint;
use App\Models\Fase;
use App\Models\Module;
use App\Models\PracticeForm;
use App\Models\Scholarship;
use App\Models\User;
use App\Models\UserPracticeResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Single source of truth for mentee progress.
 *
 * Progress is stored once per mentee and module, keyed by the module identity
 * alone. Because Inti modules are shared by every scholarship, completing one
 * Inti module completes it everywhere. Template modules belong to exactly one
 * scholarship, so their checkpoint is naturally scoped to that scholarship.
 *
 * Reading progress never writes checkpoint rows: unlock states are derived from
 * the curriculum order and only persisted when the mentee actually starts or
 * completes a module.
 */
class ProgressService implements ProgressServiceInterface
{
    public function __construct(
        private readonly LearningPathServiceInterface $learning_path,
    ) {}

    /**
     * Explicitly materialise the checkpoint rows of the mentee.
     *
     * The mentee UI never needs this: it derives unlock states on read. The
     * method stays available for maintenance tasks and admin flows that need a
     * complete snapshot of the current path.
     */
    public function ensureCheckpoints(User $user): void
    {
        /** @var array<int, Checkpoint> $existing */
        $existing = $user->checkpoints()->get()->keyBy('module_id')->all();

        foreach ($this->curriculum($user) as $module) {
            if (! isset($existing[$module->id])) {
                $user->checkpoints()->firstOrCreate(
                    ['module_id' => $module->id],
                    $this->checkpointAttributes($module, CheckpointStatus::Locked),
                );
            }
        }

        foreach ($this->learning_path->libraryModules() as $module) {
            if (! isset($existing[$module->id])) {
                $user->checkpoints()->firstOrCreate(
                    ['module_id' => $module->id],
                    $this->checkpointAttributes($module, CheckpointStatus::Available),
                );
            }
        }

        $this->syncCheckpointStates($user);
    }

    /**
     * Get the aggregate learning progress for the mentee's active scholarship.
     */
    public function summary(User $user): ProgressSummaryData
    {
        return $this->buildSummary($this->progressRows($user, $this->curriculum($user)));
    }

    /**
     * Get the dashboard payload with a single curriculum and checkpoint read.
     */
    public function overview(User $user, ?Scholarship $scholarship = null): LearningOverviewData
    {
        $rows = $this->progressRows($user, $this->curriculum($user, $scholarship));

        return new LearningOverviewData(
            summary: $this->buildSummary($rows),
            roadmap: $this->buildRoadmap($rows, $this->orderedFases()),
        );
    }

    /**
     * Get the ordered fases through the shared content cache.
     *
     * The rows are cached as plain attributes and hydrated back into models,
     * because the cache store does not unserialize PHP objects.
     *
     * @return Collection<int, Fase>
     */
    private function orderedFases(): Collection
    {
        /** @var array<int, array<string, mixed>> $rows */
        $rows = ContentCache::remember(
            'fases',
            fn (): array => Fase::query()->ordered()->get()
                ->map(fn (Fase $fase): array => $fase->getAttributes())
                ->all(),
        );

        return Fase::hydrate($rows);
    }

    /**
     * Build the aggregate summary from already resolved progress rows.
     *
     * @param  array<int, array{module: Module, status: CheckpointStatus, checkpoint: Checkpoint|null}>  $rows
     */
    private function buildSummary(array $rows): ProgressSummaryData
    {
        $completed = 0;
        $scores = [];
        $current_id = null;
        $current_title = null;
        $core_completed = 0;
        $core_total = 0;
        $template_completed = 0;
        $template_total = 0;

        foreach ($rows as $row) {
            $module = $row['module'];
            $status = $row['status'];

            if ($module->category === ModuleCategory::Inti) {
                $core_total++;
            } elseif ($module->category === ModuleCategory::Template) {
                $template_total++;
            }

            if ($status === CheckpointStatus::Completed) {
                $completed++;

                if ($module->category === ModuleCategory::Inti) {
                    $core_completed++;
                } elseif ($module->category === ModuleCategory::Template) {
                    $template_completed++;
                }

                $score = $row['checkpoint']?->score;

                if ($score !== null) {
                    $scores[] = $score;
                }

                continue;
            }

            if ($current_id === null && $status !== CheckpointStatus::Locked) {
                $current_id = $module->id;
                $current_title = $module->title;
            }
        }

        $total = count($rows);

        return new ProgressSummaryData(
            completed_modules: $completed,
            total_modules: $total,
            percentage: $total === 0 ? 0 : (int) round($completed / $total * 100),
            current_module_id: $current_id,
            current_module_title: $current_title,
            average_score: $scores === [] ? 0 : (int) round(array_sum($scores) / count($scores)),
            core_completed_modules: $core_completed,
            core_total_modules: $core_total,
            template_completed_modules: $template_completed,
            template_total_modules: $template_total,
            path_completed: $total > 0 && $completed === $total,
        );
    }

    /**
     * Get the full roadmap of the active scholarship grouped by fase.
     *
     * @return array<int, FaseProgressData>
     */
    public function roadmap(User $user): array
    {
        return $this->buildRoadmap(
            $this->progressRows($user, $this->curriculum($user)),
            $this->orderedFases(),
        );
    }

    /**
     * Get the roadmap entry that follows a module on the active path.
     */
    public function nextModule(User $user, Module $module): ?ModuleProgressData
    {
        $rows = $this->progressRows($user, $this->curriculum($user));

        foreach ($rows as $index => $row) {
            if ($row['module']->id === $module->id) {
                $next = $rows[$index + 1] ?? null;

                return $next === null ? null : $this->moduleProgress($next);
            }
        }

        return null;
    }

    /**
     * Group resolved progress rows into the fase roadmap.
     *
     * @param  array<int, array{module: Module, status: CheckpointStatus, checkpoint: Checkpoint|null}>  $rows
     * @param  Collection<int, Fase>  $fases
     * @return array<int, FaseProgressData>
     */
    private function buildRoadmap(array $rows, Collection $fases): array
    {
        /** @var array<int, array<int, array{module: Module, status: CheckpointStatus, checkpoint: Checkpoint|null}>> $rows_by_fase */
        $rows_by_fase = [];

        foreach ($rows as $row) {
            $rows_by_fase[$row['module']->fase_id][] = $row;
        }

        $roadmap = [];

        foreach ($fases as $fase) {
            $fase_rows = $rows_by_fase[$fase->id] ?? null;

            // Fases without a module on the mentee's path are skipped so the
            // roadmap never shows an empty scholarship section.
            if ($fase_rows === null) {
                continue;
            }

            $modules_data = [];
            $completed = 0;

            foreach ($fase_rows as $row) {
                $module = $row['module'];
                $status = $row['status'];

                if ($status === CheckpointStatus::Completed) {
                    $completed++;
                }

                $modules_data[] = $this->moduleProgress($row);
            }

            $total = count($modules_data);

            $roadmap[] = new FaseProgressData(
                id: $fase->id,
                title: $fase->title,
                subtitle: $fase->subtitle,
                fase_order: $fase->fase_order,
                modules: $modules_data,
                completed_modules: $completed,
                total_modules: $total,
                percentage: $total === 0 ? 0 : (int) round($completed / $total * 100),
            );
        }

        return $roadmap;
    }

    /**
     * Build one roadmap entry from a resolved progress row.
     *
     * @param  array{module: Module, status: CheckpointStatus, checkpoint: Checkpoint|null}  $row
     */
    private function moduleProgress(array $row): ModuleProgressData
    {
        $module = $row['module'];

        return new ModuleProgressData(
            id: $module->id,
            fase_id: $module->fase_id,
            title: $module->title,
            modul_order: $module->modul_order,
            type: $module->type,
            category: $module->category,
            duration: $module->duration,
            status: $row['status'],
            score: $row['checkpoint']?->score,
            has_practice_form: ($module->practice_forms_count ?? 0) > 0,
            scholarship_id: $module->scholarship_id,
        );
    }

    /**
     * Get the checkpoint of a module for the mentee.
     *
     * When the mentee has never touched the module, an unsaved checkpoint with
     * the derived status is returned so callers can display the real lock state
     * without writing a row just because a page was rendered.
     */
    public function checkpointFor(User $user, Module $module): ?Checkpoint
    {
        $checkpoint = $this->storedCheckpoint($user, $module);

        if ($module->category === ModuleCategory::Perpustakaan) {
            return $checkpoint ?? new Checkpoint($this->checkpointAttributes($module, CheckpointStatus::Available));
        }

        $row = $this->curriculumRow($user, $module);

        if ($row === null) {
            return null;
        }

        // The stored row can carry a state from another scholarship path (for
        // example a legacy row that was materialised as locked). The derived
        // state is the truth for the active curriculum, so expose it here
        // without writing anything.
        $status = $row['status'];

        if ($checkpoint instanceof Checkpoint) {
            // Return a read model so callers cannot accidentally persist a
            // derived state over the stored history.
            $read_model = clone $checkpoint;
            $read_model->status = $status;

            return $read_model;
        }

        return new Checkpoint($this->checkpointAttributes($module, $status));
    }

    /**
     * Mark a module as started.
     */
    public function startModule(User $user, Module $module): Checkpoint
    {
        $status = $this->authorisedStatus($user, $module);

        if ($status === CheckpointStatus::Locked) {
            throw new AuthorizationException('Modul ini masih terkunci. Selesaikan modul sebelumnya dulu ya.');
        }

        $checkpoint = $this->storedCheckpoint($user, $module)
            ?? $user->checkpoints()->firstOrCreate(
                ['module_id' => $module->id],
                $this->checkpointAttributes($module, $status),
            );

        if ($checkpoint->status === CheckpointStatus::Available || $checkpoint->status === CheckpointStatus::Locked) {
            $checkpoint->status = CheckpointStatus::InProgress;
            $checkpoint->started_at = now();
            $checkpoint->available_at ??= now();
            $checkpoint->save();
        }

        return $checkpoint;
    }

    /**
     * Mark a module as completed and unlock the next module of the path.
     */
    public function completeModule(User $user, Module $module, ?int $score = null): Checkpoint
    {
        $status = $this->authorisedStatus($user, $module);

        if ($status === CheckpointStatus::Locked) {
            throw new AuthorizationException('Modul ini masih terkunci. Selesaikan modul sebelumnya dulu ya.');
        }

        $this->assertPracticeSubmitted($user, $module);

        $checkpoint = $this->storedCheckpoint($user, $module)
            ?? $user->checkpoints()->firstOrCreate(
                ['module_id' => $module->id],
                $this->checkpointAttributes($module, $status),
            );

        // A duplicated confirmation must not inflate attempts or move the
        // completion timestamp on an already finished module.
        if ($checkpoint->isCompleted()) {
            return $checkpoint;
        }

        $checkpoint->status = CheckpointStatus::Completed;
        $checkpoint->completed_at = now();
        $checkpoint->attempts = $checkpoint->attempts + 1;
        $checkpoint->available_at ??= now();

        if ($score !== null) {
            $checkpoint->score = $score;
        }

        $checkpoint->save();

        $this->syncCheckpointStates($user);

        return $checkpoint;
    }

    /**
     * Determine whether every mandatory module of a scholarship is completed.
     *
     * Library modules are intentionally excluded: they are optional training
     * and never gate the scholarship path.
     */
    public function isPathCompleted(User $user, ?Scholarship $scholarship = null): bool
    {
        $modules = $scholarship === null
            ? $this->curriculum($user)
            : $this->learning_path->curriculumModules($scholarship);

        if ($modules->isEmpty()) {
            return false;
        }

        /** @var array<int, int> $completed */
        $completed = $user->checkpoints()
            ->whereIn('module_id', $modules->pluck('id')->all())
            ->where('status', CheckpointStatus::Completed->value)
            ->pluck('module_id')
            ->all();

        return count(array_unique($completed)) === $modules->count();
    }

    /**
     * Get the curriculum modules of the mentee's active scholarship.
     *
     * @return Collection<int, Module>
     */
    public function curriculum(User $user, ?Scholarship $scholarship = null): Collection
    {
        if ($scholarship instanceof Scholarship) {
            return $this->learning_path->curriculumModules($scholarship);
        }

        return $this->learning_path->curriculumModules($this->learning_path->activeScholarship($user));
    }

    /**
     * Derive the status of every curriculum module from completion order.
     *
     * @param  Collection<int, Module>  $modules
     * @return array<int, array{module: Module, status: CheckpointStatus, checkpoint: Checkpoint|null}>
     */
    private function progressRows(User $user, Collection $modules): array
    {
        /** @var array<int, Checkpoint> $checkpoints */
        $checkpoints = $user->checkpoints()
            ->whereIn('module_id', $modules->pluck('id')->all())
            ->get()
            ->keyBy('module_id')
            ->all();

        $rows = [];
        $previous_completed = true;

        foreach ($modules as $module) {
            $checkpoint = $checkpoints[$module->id] ?? null;

            if ($checkpoint?->isCompleted()) {
                $status = CheckpointStatus::Completed;
                $previous_completed = true;
            } elseif (! $previous_completed) {
                $status = CheckpointStatus::Locked;
            } elseif ($checkpoint?->started_at !== null) {
                $status = CheckpointStatus::InProgress;
                $previous_completed = false;
            } else {
                $status = CheckpointStatus::Available;
                $previous_completed = false;
            }

            $rows[] = [
                'module' => $module,
                'status' => $status,
                'checkpoint' => $checkpoint,
            ];
        }

        return $rows;
    }

    /**
     * Resolve the progress row of a module on the active path.
     *
     * @return array{module: Module, status: CheckpointStatus, checkpoint: Checkpoint|null}|null
     */
    private function curriculumRow(User $user, Module $module): ?array
    {
        foreach ($this->progressRows($user, $this->curriculum($user)) as $row) {
            if ($row['module']->id === $module->id) {
                return $row;
            }
        }

        return null;
    }

    /**
     * Resolve and authorise the status of a module the mentee wants to act on.
     *
     * Template modules of any other scholarship are rejected here, so a stale
     * bookmark or a tampered URL can never open material that is not on the
     * active path.
     *
     * @throws AuthorizationException
     */
    private function authorisedStatus(User $user, Module $module): CheckpointStatus
    {
        if ($module->category === ModuleCategory::Perpustakaan) {
            $checkpoint = $this->storedCheckpoint($user, $module);

            return $checkpoint instanceof Checkpoint ? $checkpoint->status : CheckpointStatus::Available;
        }

        $row = $this->curriculumRow($user, $module);

        if ($row === null) {
            throw new AuthorizationException('Modul ini tidak tersedia pada jalur belajarmu.');
        }

        return $row['status'];
    }

    /**
     * Persist unlock states after a module changes state.
     *
     * Only modules that are now reachable get a row; locked modules stay
     * virtual until the mentee reaches them.
     */
    private function syncCheckpointStates(User $user): void
    {
        foreach ($this->progressRows($user, $this->curriculum($user)) as $row) {
            $checkpoint = $row['checkpoint'];
            $status = $row['status'];

            if ($checkpoint === null) {
                if ($status !== CheckpointStatus::Locked) {
                    $user->checkpoints()->firstOrCreate(
                        ['module_id' => $row['module']->id],
                        $this->checkpointAttributes($row['module'], $status),
                    );
                }

                continue;
            }

            if ($checkpoint->isCompleted() || $checkpoint->status === $status) {
                continue;
            }

            $checkpoint->status = $status;
            $checkpoint->available_at = $status === CheckpointStatus::Locked
                ? null
                : ($checkpoint->available_at ?? now());
            $checkpoint->save();
        }
    }

    /**
     * Get the persisted checkpoint of a module, if any.
     */
    private function storedCheckpoint(User $user, Module $module): ?Checkpoint
    {
        return $user->checkpoints()->where('module_id', $module->id)->first();
    }

    /**
     * Ensure every active practice form of the module has been submitted.
     *
     * The mentee may still unlock a checkpoint when the AI evaluation failed,
     * because a failed submission is stored too — only missing answers block it.
     *
     * @throws ValidationException
     */
    private function assertPracticeSubmitted(User $user, Module $module): void
    {
        /** @var Collection<int, int> $form_ids */
        $form_ids = PracticeForm::query()
            ->where('module_id', $module->id)
            ->where('is_active', true)
            ->pluck('id');

        if ($form_ids->isEmpty()) {
            return;
        }

        /** @var Collection<int, int> $answered */
        $answered = UserPracticeResponse::query()
            ->where('user_id', $user->id)
            ->whereIn('practice_form_id', $form_ids->all())
            ->pluck('practice_form_id');

        if ($form_ids->diff($answered)->isNotEmpty()) {
            throw ValidationException::withMessages([
                'checkpoint' => 'Selesaikan semua latihan praktik modul ini dulu sebelum mengunci checkpoint.',
            ]);
        }
    }

    /**
     * Build the attributes of a checkpoint row.
     *
     * @return array<string, mixed>
     */
    private function checkpointAttributes(Module $module, CheckpointStatus $status): array
    {
        return [
            'module_id' => $module->id,
            'fase_id' => $module->fase_id,
            'status' => $status,
            'score' => null,
            'attempts' => 0,
            'metadata' => null,
            'available_at' => in_array($status, [CheckpointStatus::Available, CheckpointStatus::InProgress], true) ? now() : null,
            'started_at' => null,
            'completed_at' => null,
        ];
    }
}
