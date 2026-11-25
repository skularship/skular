<?php

namespace App\Enums;

use App\Enums\Concerns\HasSelectOptions;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Subtest of the Tes IQ Latihan, in the order the questions are numbered
 * (Numerik 1-7, Visuospasial 8-14, Verbal 15-19, Logika 20-27, Teks 28-35).
 * Visuospasial questions need an image with alt text before they go live.
 * Stored values; never rename.
 */
enum IqSubtest: string implements HasColor, HasLabel
{
    use HasSelectOptions;

    case Numerik = 'numerik';
    case Visuospasial = 'visuospasial';
    case Verbal = 'verbal';
    case Logika = 'logika';
    case Teks = 'teks';

    /**
     * Get the label shown to mentees and admins.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::Numerik => 'Numerik',
            self::Visuospasial => 'Visuospasial',
            self::Verbal => 'Verbal',
            self::Logika => 'Logika',
            self::Teks => 'Teks',
        };
    }

    /**
     * Get the Filament color of the subtest badge.
     */
    public function getColor(): string
    {
        return match ($this) {
            self::Numerik => 'info',
            self::Visuospasial => 'warning',
            self::Verbal => 'success',
            self::Logika => 'primary',
            self::Teks => 'gray',
        };
    }

    /**
     * Get the heroicon of the subtest.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Numerik => 'calculator',
            self::Visuospasial => 'squares-2x2',
            self::Verbal => 'language',
            self::Logika => 'puzzle-piece',
            self::Teks => 'document-text',
        };
    }

    /**
     * Whether a question of this subtest must show an image (with alt text).
     */
    public function requiresImage(): bool
    {
        return $this === self::Visuospasial;
    }
}
