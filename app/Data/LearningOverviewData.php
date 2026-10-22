<?php

namespace App\Data;

use Spatie\LaravelData\Data;

/**
 * Dashboard payload: the aggregate summary plus the fase roadmap.
 *
 * Both are produced from a single curriculum and checkpoint read so the
 * dashboard never computes the same progress twice.
 */
class LearningOverviewData extends Data
{
    /**
     * @param  array<int, FaseProgressData>  $roadmap
     */
    public function __construct(
        public ProgressSummaryData $summary,
        public array $roadmap,
    ) {}
}
