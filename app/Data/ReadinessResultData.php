<?php

namespace App\Data;

use App\Enums\ReadinessCategory;
use Spatie\LaravelData\Data;

/**
 * The deterministic outcome of a Profiling Kilat assessment.
 */
class ReadinessResultData extends Data
{
    /**
     * @param  array<int, string>  $recommendations
     */
    public function __construct(
        public int $score,
        public ReadinessCategory $category,
        public string $headline,
        public string $subheadline,
        public string $blocker,
        public array $recommendations,
        public string $timeline_summary,
    ) {}

    /**
     * Get the human readable band label for the readiness score.
     */
    public function categoryLabel(): string
    {
        return $this->category->label();
    }
}
