<?php

namespace App\Contracts;

use App\Data\ReadinessResultData;
use App\Enums\QuickProfilingAsset;
use App\Enums\QuickProfilingGoal;
use App\Enums\QuickProfilingStatus;
use App\Enums\QuickProfilingTimeline;
use App\Enums\QuickProfilingWeeklyTime;

interface ReadinessScoringServiceInterface
{
    /**
     * Turn a finished Profiling Kilat submission into a readiness result.
     *
     * The scoring is deterministic: the same answers always produce the same
     * score, band, blocker and recommendations.
     *
     * @param  array<int, QuickProfilingAsset>  $assets
     */
    public function score(
        QuickProfilingStatus $status,
        QuickProfilingGoal $goal,
        QuickProfilingTimeline $timeline,
        array $assets,
        QuickProfilingWeeklyTime $weekly_time,
    ): ReadinessResultData;
}
