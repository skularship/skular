<?php

namespace App\Enums;

use App\Enums\Concerns\HasSelectOptions;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Selection result a mentee reports for a scholarship application (accepted or rejected).
 */
enum ApplicationResult: string implements HasColor, HasLabel
{
    use HasSelectOptions;

    case Accepted = 'accepted';
    case Rejected = 'rejected';

    /**
     * Get the Indonesian label shown to mentees and in the Filament admin.
     *
     * @return string Human readable label.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::Accepted => 'Lulus',
            self::Rejected => 'Belum Lulus',
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
            self::Accepted => 'success',
            self::Rejected => 'danger',
        };
    }

    /**
     * Determine whether this result unlocks the twibbon generator.
     */
    public function qualifiesForTwibbon(): bool
    {
        return $this === self::Accepted;
    }
}
