<?php

namespace App\Data;

use App\Enums\ActivityName;
use Spatie\LaravelData\Data;

/**
 * One step of the admin mentee funnel: how many mentees of the cohort reached
 * it, and the share that continued from the previous step.
 */
class FunnelStepData extends Data
{
    /**
     * @param  ActivityName  $step  The tracked action of this step.
     * @param  int  $count  Distinct mentees of the cohort who did it.
     * @param  int|null  $continued_percentage  Share of the previous step (null for the first step or an empty previous step).
     */
    public function __construct(
        public ActivityName $step,
        public int $count,
        public ?int $continued_percentage,
    ) {}
}
