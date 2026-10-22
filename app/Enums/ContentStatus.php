<?php

namespace App\Enums;

use App\Enums\Concerns\HasSelectOptions;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Editorial status of a curriculum record.
 *
 * - Draft: still being written by the curriculum team, hidden from mentees.
 * - Published: finished and part of the mentee learning experience.
 */
enum ContentStatus: string implements HasColor, HasLabel
{
    use HasSelectOptions;

    case Draft = 'draft';
    case Published = 'published';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Published => 'Terbit',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'warning',
            self::Published => 'success',
        };
    }

    /**
     * Determine whether the record is visible to mentees.
     */
    public function isPublished(): bool
    {
        return $this === self::Published;
    }
}
