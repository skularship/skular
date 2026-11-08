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
    /**
     * @param  int  $completed_modules  Completed modules on the active path.
     * @param  int  $total_modules  Modules on the active path.
     * @param  int  $percentage  Completion percentage (0-100).
     * @param  int|null  $current_module_id  Module the mentee should open next.
     * @param  string|null  $current_module_title  Title of that module.
     * @param  int  $average_score  Internal average AI score (0 when none); mentees see AiScoreLabel.
     * @param  int  $core_completed_modules  Completed shared Inti modules.
     * @param  int  $core_total_modules  Shared Inti modules.
     * @param  int  $template_completed_modules  Completed Template modules of the active scholarship.
     * @param  int  $template_total_modules  Template modules of the active scholarship.
     * @param  bool  $path_completed  Whether the active path was explicitly finished.
     */
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
