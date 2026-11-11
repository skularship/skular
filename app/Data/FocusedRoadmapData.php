<?php

namespace App\Data;

use Spatie\LaravelData\Data;

/**
 * The roadmap as the mentee may see it: finished fases, the active fase with
 * its modules, and only the names of the fases still ahead.
 *
 * Built by ProgressService::focusRoadmap(); fases ahead are FaseOutlineData,
 * which has no module fields at all.
 */
class FocusedRoadmapData extends Data
{
    /**
     * @param  array<int, FaseProgressData>  $completed  Fully finished fases, in roadmap order.
     * @param  FaseProgressData|null  $active  The fase holding the first unfinished module.
     * @param  FaseOutlineData|null  $upcoming  The fase right after the active one (name only).
     * @param  array<int, FaseOutlineData>  $locked  Every later unfinished fase (name only).
     */
    public function __construct(
        public array $completed,
        public ?FaseProgressData $active,
        public ?FaseOutlineData $upcoming,
        public array $locked,
    ) {}

    /**
     * Determine whether the roadmap has no module at all.
     */
    public function isEmpty(): bool
    {
        return $this->completed === [] && $this->active === null;
    }

    /**
     * Determine whether every fase on the roadmap is finished.
     */
    public function isFinished(): bool
    {
        return $this->completed !== [] && $this->active === null;
    }
}
