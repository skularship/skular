<?php

namespace App\Contracts;

use App\Data\CvSectionData;
use App\Models\Module;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Builds the mentee CV page from practice answers whose form is flagged "include in CV",
 * and the read-only CV reference panel shown on a module's Praktik step.
 */
interface CvServiceInterface
{
    /**
     * Build the mentee CV sections from practice answers flagged by the admin.
     *
     * @param  User  $user  The mentee whose answers are read.
     * @return Collection<int, CvSectionData> Only sections with at least one answer.
     */
    public function sections(User $user): Collection;

    /**
     * Build the CV reference sources an admin linked to a module.
     *
     * Every visible source is returned, answered or not (`filled = false`),
     * so the mentee always sees where to fill it in. Read only: nothing is
     * written and no AI is called.
     *
     * @param  User  $user  The signed-in mentee; only their answers are read.
     * @param  Module  $module  The module whose Praktik step shows the panel.
     * @return Collection<int, CvSectionData> Sources in CV order.
     */
    public function sectionsForModule(User $user, Module $module): Collection;
}
