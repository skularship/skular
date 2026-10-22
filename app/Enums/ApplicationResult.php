<?php

namespace App\Enums;

use App\Enums\Concerns\HasSelectOptions;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ApplicationResult: string implements HasColor, HasLabel
{
    use HasSelectOptions;

    case Accepted = 'accepted';
    case Rejected = 'rejected';

    public function getLabel(): string
    {
        return match ($this) {
            self::Accepted => 'Lulus',
            self::Rejected => 'Belum Lulus',
        };
    }

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
