<?php

namespace App\Services;

use App\Contracts\LearningPathServiceInterface;
use App\Contracts\ScholarshipApplicationServiceInterface;
use App\Enums\ContentStatus;
use App\Enums\ModuleCategory;
use App\Models\Module;
use App\Models\Scholarship;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class LearningPathService implements LearningPathServiceInterface
{
    public function __construct(
        private readonly ScholarshipApplicationServiceInterface $applications,
    ) {}

    /**
     * Get the ordered curriculum of the scholarship the mentee is preparing.
     *
     * Shared Inti modules come first inside each fase, followed by the Template
     * modules that belong to the active scholarship only. Library modules are
     * never part of this path.
     *
     * @return Collection<int, Module>
     */
    public function roadmapModules(User $user): Collection
    {
        return $this->curriculumModules($this->activeScholarship($user));
    }

    /**
     * Get the published, ordered curriculum of a single scholarship.
     *
     * Passing null resolves the shared Inti foundation only, which keeps the
     * dashboard useful for a mentee who has not picked a scholarship yet.
     *
     * @return Collection<int, Module>
     */
    public function curriculumModules(?Scholarship $scholarship): Collection
    {
        $scholarship_id = $scholarship?->id;

        return $this->cachedModules(
            'curriculum:'.($scholarship_id ?? 'core'),
            fn (): Builder => $this->orderedQuery()
                ->whereIn('modules.category', ModuleCategory::roadmapValues())
                ->where(function (Builder $query) use ($scholarship_id): void {
                    $query->where('modules.category', ModuleCategory::Inti->value);

                    if ($scholarship_id !== null) {
                        $query->orWhere(fn (Builder $inner): Builder => $inner
                            ->where('modules.category', ModuleCategory::Template->value)
                            ->where('modules.scholarship_id', $scholarship_id));
                    }
                }),
        );
    }

    /**
     * Get the scholarship whose learning path is currently in focus.
     */
    public function activeScholarship(User $user): ?Scholarship
    {
        return $this->applications->activeApplication($user)?->scholarship;
    }

    /**
     * Get the shared Inti modules that apply to every scholarship.
     *
     * @return Collection<int, Module>
     */
    public function coreModules(): Collection
    {
        return $this->cachedModules(
            'curriculum:core',
            fn (): Builder => $this->orderedQuery()
                ->where('modules.category', ModuleCategory::Inti->value),
        );
    }

    /**
     * Get the Template modules belonging to the active scholarship.
     *
     * @return Collection<int, Module>
     */
    public function templateModules(User $user): Collection
    {
        $scholarship = $this->activeScholarship($user);

        return $scholarship === null ? new Collection : $this->templateModulesFor($scholarship);
    }

    /**
     * Get the Template modules tailored to the given scholarship.
     *
     * @return Collection<int, Module>
     */
    public function templateModulesFor(Scholarship $scholarship): Collection
    {
        return $this->cachedModules(
            'template:'.$scholarship->id,
            fn (): Builder => $this->orderedQuery()
                ->where('modules.category', ModuleCategory::Template->value)
                ->where('modules.scholarship_id', $scholarship->id),
        );
    }

    /**
     * Get the cross-scholarship Perpustakaan modules.
     *
     * @return Collection<int, Module>
     */
    public function libraryModules(): Collection
    {
        return $this->cachedModules(
            'library',
            fn (): Builder => $this->orderedQuery()
                ->where('modules.category', ModuleCategory::Perpustakaan->value),
        );
    }

    /**
     * Determine whether a module belongs to the mentee's active curriculum.
     */
    public function isOnRoadmap(User $user, Module $module): bool
    {
        if ($module->category === ModuleCategory::Perpustakaan) {
            return false;
        }

        return $this->roadmapModules($user)->contains(
            fn (Module $candidate): bool => $candidate->id === $module->id,
        );
    }

    /**
     * Determine whether a module belongs to the curriculum of a scholarship.
     *
     * This is the authorisation rule behind opening a module: a Template module
     * of another scholarship, or a Template module of a scholarship that is no
     * longer in focus, is never part of this curriculum.
     */
    public function isCurriculumModule(?Scholarship $scholarship, Module $module): bool
    {
        return $this->curriculumModules($scholarship)->contains(
            fn (Module $candidate): bool => $candidate->id === $module->id,
        );
    }

    /**
     * Read a module selector through the content cache.
     *
     * Only the attribute rows are cached, never the models themselves: the
     * cache store does not unserialize PHP objects, so the models are rebuilt
     * from the cached rows on every read.
     *
     * @param  callable(): Builder<Module>  $query
     * @return Collection<int, Module>
     */
    private function cachedModules(string $namespace, callable $query): Collection
    {
        /** @var array<int, array<string, mixed>> $rows */
        $rows = ContentCache::remember(
            $namespace,
            fn (): array => $query()->get()
                ->map(fn (Module $module): array => $module->getAttributes())
                ->all(),
        );

        return Module::hydrate($rows);
    }

    /**
     * Start a phase-first, module-ordered query shared by every selector.
     *
     * Draft modules are filtered out here, so no mentee-facing selector can
     * leak content the curriculum team has not published yet.
     *
     * @return Builder<Module>
     */
    private function orderedQuery(): Builder
    {
        return Module::query()
            ->select('modules.*')
            ->where('modules.content_status', ContentStatus::Published->value)
            ->withCount('practiceForms')
            ->join('fases', 'fases.id', '=', 'modules.fase_id')
            ->orderBy('fases.fase_order')
            ->orderBy('modules.modul_order')
            ->orderBy('modules.id');
    }
}
