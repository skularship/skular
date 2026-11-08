<?php

namespace App\Enums;

use App\Enums\Concerns\HasSelectOptions;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * How well a scholarship matches the mentee profile on the recommendation page.
 */
enum MatchTier: string implements HasColor, HasLabel
{
    use HasSelectOptions;

    case VeryGood = 'sangat_cocok';
    case Good = 'cocok';
    case Low = 'kurang_cocok';

    /**
     * Get the Indonesian label shown to mentees and in the Filament admin.
     *
     * @return string Human readable label.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::VeryGood => 'Sangat Cocok',
            self::Good => 'Cocok',
            self::Low => 'Kurang Cocok',
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
            self::VeryGood => 'success',
            self::Good => 'info',
            self::Low => 'warning',
        };
    }

    /**
     * Get the sort weight used to rank matched scholarships.
     */
    public function weight(): int
    {
        return match ($this) {
            self::VeryGood => 3,
            self::Good => 2,
            self::Low => 1,
        };
    }
}
