<?php

namespace App\Data;

use App\Enums\CheckpointStatus;
use App\Enums\ModuleCategory;
use App\Enums\ModuleType;
use Spatie\LaravelData\Data;

/**
 * Progress state of a single module inside the roadmap.
 */
class ModuleProgressData extends Data
{
    public function __construct(
        public int $id,
        public int $fase_id,
        public string $title,
        public int $modul_order,
        public ModuleType $type,
        public ModuleCategory $category,
        public ?int $duration,
        public CheckpointStatus $status,
        public ?int $score,
        public bool $has_practice_form,
        public ?int $scholarship_id = null,
    ) {}

    /**
     * Determine whether the module is shared Inti material.
     *
     * Shared modules carry their progress across every scholarship path, so
     * the roadmap can explain why they already look completed.
     */
    public function isShared(): bool
    {
        return $this->category === ModuleCategory::Inti;
    }

    /**
     * Determine whether the module is tailored to the active scholarship.
     */
    public function isScholarshipSpecific(): bool
    {
        return $this->category === ModuleCategory::Template;
    }

    /**
     * Determine whether the module has been finished.
     */
    public function isCompleted(): bool
    {
        return $this->status === CheckpointStatus::Completed;
    }

    /**
     * Determine whether the module is still locked.
     */
    public function isLocked(): bool
    {
        return $this->status === CheckpointStatus::Locked;
    }

    /**
     * Determine whether the mentee is currently working on this module.
     */
    public function isInProgress(): bool
    {
        return $this->status === CheckpointStatus::InProgress;
    }

    /**
     * Determine whether the module can be opened right now.
     */
    public function isUnlocked(): bool
    {
        return $this->status !== CheckpointStatus::Locked;
    }

    /**
     * Get the icon that represents the roadmap state.
     */
    public function statusIcon(): string
    {
        return match ($this->status) {
            CheckpointStatus::Completed => 'check',
            CheckpointStatus::InProgress => 'play',
            CheckpointStatus::Available => 'sparkles',
            CheckpointStatus::Locked => 'lock-closed',
        };
    }
}
