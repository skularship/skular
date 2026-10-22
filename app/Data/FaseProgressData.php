<?php

namespace App\Data;

use Spatie\LaravelData\Data;

/**
 * Progress state of a fase and every module it contains.
 */
class FaseProgressData extends Data
{
    /**
     * @param  array<int, ModuleProgressData>  $modules
     */
    public function __construct(
        public int $id,
        public string $title,
        public ?string $subtitle,
        public int $fase_order,
        public array $modules,
        public int $completed_modules,
        public int $total_modules,
        public int $percentage,
    ) {}

    /**
     * Determine whether every module in the fase has been completed.
     */
    public function isCompleted(): bool
    {
        return $this->total_modules > 0 && $this->completed_modules === $this->total_modules;
    }
}
