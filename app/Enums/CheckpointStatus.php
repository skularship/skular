<?php

namespace App\Enums;

use App\Enums\Concerns\HasSelectOptions;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Unlock/progress state of a module for one mentee (checkpoints.status): locked, available, in progress or completed.
 */
enum CheckpointStatus: string implements HasColor, HasLabel
{
    use HasSelectOptions;

    case Locked = 'locked';
    case Available = 'available';
    case InProgress = 'in_progress';
    case Completed = 'completed';

    /**
     * Get the Indonesian label shown to mentees and in the Filament admin.
     *
     * @return string Human readable label.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::Locked => 'Terkunci',
            self::Available => 'Tersedia',
            self::InProgress => 'Sedang Berjalan',
            self::Completed => 'Selesai',
        };
    }

    /**
     * Get the Filament color used for badges of this case.
     *
     * @return string Filament color name (gray, info, success, warning, danger, ...).
     */
    public function getColor(): string
    {
        return match ($this) {
            self::Locked => 'gray',
            self::Available => 'info',
            self::InProgress => 'warning',
            self::Completed => 'success',
        };
    }

    /**
     * Determine whether the checkpoint counts towards completed progress.
     */
    public function isCompleted(): bool
    {
        return $this === self::Completed;
    }
}
