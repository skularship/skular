<?php

namespace App\Data;

use Spatie\LaravelData\Data;

/**
 * Aggregate learning progress for the mentee dashboard.
 *
 * The counts are kept per curriculum layer so a shared Inti module is never
 * counted twice when the mentee prepares several scholarships.
 */
class ProgressSummaryData extends Data
{
    public function __construct(
        public int $completed_modules,
        public int $total_modules,
        public int $percentage,
        public ?int $current_module_id,
        public ?string $current_module_title,
        public int $average_score,
        public int $core_completed_modules = 0,
        public int $core_total_modules = 0,
        public int $template_completed_modules = 0,
        public int $template_total_modules = 0,
        public bool $path_completed = false,
    ) {}

    /**
     * Determine whether the mentee finished every module of the active path.
     */
    public function isFinished(): bool
    {
        return $this->path_completed;
    }

    /**
     * Determine whether the mentee has at least one module to work on.
     */
    public function hasModules(): bool
    {
        return $this->total_modules > 0;
    }
}
