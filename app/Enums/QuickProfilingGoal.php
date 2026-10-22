<?php

namespace App\Enums;

use App\Enums\Concerns\HasSelectOptions;
use Filament\Support\Contracts\HasLabel;

/**
 * The answer to the "apa yang mau kamu kejar?" question of Profiling Kilat.
 */
enum QuickProfilingGoal: string implements HasLabel
{
    use HasSelectOptions;

    case BeasiswaS1 = 'beasiswa_s1';
    case S2DalamNegeri = 's2_dalam_negeri';
    case S2LuarNegeri = 's2_luar_negeri';
    case Lomba = 'lomba';

    public function getLabel(): string
    {
        return match ($this) {
            self::BeasiswaS1 => 'Beasiswa S1',
            self::S2DalamNegeri => 'S2 Dalam Negeri',
            self::S2LuarNegeri => 'S2 Luar Negeri',
            self::Lomba => 'Lomba',
        };
    }

    /**
     * Get the supporting helper text shown under the option label.
     */
    public function description(): string
    {
        return match ($this) {
            self::BeasiswaS1 => 'Mencari beasiswa untuk jenjang sarjana.',
            self::S2DalamNegeri => 'Melanjutkan magister di kampus Indonesia.',
            self::S2LuarNegeri => 'Melanjutkan magister di kampus luar negeri.',
            self::Lomba => 'Mengikuti kompetisi untuk menambah prestasi.',
        };
    }

    /**
     * Get the Heroicon name used on the selection card.
     */
    public function icon(): string
    {
        return match ($this) {
            self::BeasiswaS1 => 'academic-cap',
            self::S2DalamNegeri => 'flag',
            self::S2LuarNegeri => 'globe-alt',
            self::Lomba => 'trophy',
        };
    }
}
