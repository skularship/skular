<?php

namespace App\Contracts;

use App\Models\Module;
use App\Models\Scholarship;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Resolves which modules belong to a mentee's personalised learning path.
 *
 * The resolved path is always: shared Inti modules + the Template modules of
 * the scholarship that is currently in focus. Library modules are never mixed
 * into this path.
 */
interface LearningPathServiceInterface
{
    /**
     * Get the ordered curriculum of the scholarship the mentee is preparing.
     *
     * @return Collection<int, Module>
     */
    public function roadmapModules(User $user): Collection;

    /**
     * Get the published, ordered curriculum of a single scholarship.
     *
     * Passing null resolves the shared Inti foundation only.
     *
     * @return Collection<int, Module>
     */
    public function curriculumModules(?Scholarship $scholarship): Collection;

    /**
     * Get the scholarship whose learning path is currently in focus.
     */
    public function activeScholarship(User $user): ?Scholarship;

    /**
     * Get the shared Inti modules that apply to every scholarship.
     *
     * @return Collection<int, Module>
     */
    public function coreModules(): Collection;

    /**
     * Get the Template modules belonging to the active scholarship.
     *
     * @return Collection<int, Module>
     */
    public function templateModules(User $user): Collection;

    /**
     * Get the Template modules tailored to the given scholarship.
     *
     * @return Collection<int, Module>
     */
    public function templateModulesFor(Scholarship $scholarship): Collection;

    /**
     * Get the cross-scholarship Perpustakaan modules.
     *
     * @return Collection<int, Module>
     */
    public function libraryModules(): Collection;

    /**
     * Determine whether a module belongs to the mentee's active curriculum.
     */
    public function isOnRoadmap(User $user, Module $module): bool;

    /**
     * Determine whether a module belongs to the curriculum of a scholarship.
     */
    public function isCurriculumModule(?Scholarship $scholarship, Module $module): bool;
}
