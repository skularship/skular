<?php

namespace App\Enums;

use App\Enums\Concerns\HasSelectOptions;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Lifecycle of one Try Out attempt: in_progress -> completed, or abandoned
 * when an admin closes a stuck attempt. Stored values; never rename.
 */
enum TryoutAttemptStatus: string implements HasColor, HasLabel
{
    use HasSelectOptions;

    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Expired = 'expired';
    case Abandoned = 'abandoned';

    /**
     * Get the Indonesian label.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::InProgress => 'Sedang dikerjakan',
            self::Completed => 'Selesai',
            self::Expired => 'Kedaluwarsa',
            self::Abandoned => 'Ditutup',
        };
    }

    /**
     * Get the Filament badge color.
     */
    public function getColor(): string
    {
        return match ($this) {
            self::InProgress => 'warning',
            self::Completed => 'success',
            self::Expired, self::Abandoned => 'gray',
        };
    }
}
