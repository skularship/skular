<?php

namespace App\Contracts;

use App\Data\FunnelStepData;
use App\Enums\ActivityName;
use App\Models\User;

/**
 * Records mentee behaviour (which pages they open and what they click) in
 * activity_logs, without third-party tools, and summarises it as a funnel.
 */
interface ActivityTrackerInterface
{
    /**
     * Record one action. Never throws: tracking must not break the main flow.
     *
     * @param  ActivityName  $name  The tracked action.
     * @param  array<string, mixed>  $properties  Small context, e.g. ['module_id' => 3].
     * @param  User|null  $user  The mentee; defaults to the signed-in user (null for guests).
     */
    public function track(ActivityName $name, array $properties = [], ?User $user = null): void;

    /**
     * Count the funnel of mentees who registered in the last $days days.
     *
     * Each step counts distinct mentees of that cohort who did the action at
     * any time, so a step can be reached without the previous one.
     *
     * @param  int  $days  Size of the registration window.
     * @return array<int, FunnelStepData>
     */
    public function funnel(int $days): array;
}
