<?php

namespace App\Enums;

use App\Enums\Concerns\HasSelectOptions;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CheckpointStatus: string implements HasColor, HasLabel
{
    use HasSelectOptions;

    case Locked = 'locked';
    case Available = 'available';
    case InProgress = 'in_progress';
    case Completed = 'completed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Locked => 'Terkunci',
            self::Available => 'Tersedia',
            self::InProgress => 'Sedang Berjalan',
            self::Completed => 'Selesai',
        };
    }

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
