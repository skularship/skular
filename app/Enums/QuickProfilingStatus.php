<?php

namespace App\Enums;

use App\Enums\Concerns\HasSelectOptions;
use Filament\Support\Contracts\HasLabel;

/**
 * The answer to the "status kamu sekarang?" question of Profiling Kilat.
 */
enum QuickProfilingStatus: string implements HasLabel
{
    use HasSelectOptions;

    case SMA = 'sma';
    case S1 = 's1';
    case FreshGraduate = 'fresh_graduate';
    case Worker = 'worker';

    public function getLabel(): string
    {
        return match ($this) {
            self::SMA => 'SMA',
            self::S1 => 'S1 Aktif',
            self::FreshGraduate => 'Fresh Graduate',
            self::Worker => 'Pekerja',
        };
    }

    /**
     * Get the supporting helper text shown under the option label.
     */
    public function description(): string
    {
        return match ($this) {
            self::SMA => 'Masih menempuh pendidikan menengah.',
            self::S1 => 'Sedang aktif kuliah sarjana.',
            self::FreshGraduate => 'Baru lulus dan belum menempuh jenjang berikutnya.',
            self::Worker => 'Sudah bekerja atau menekuni karier.',
        };
    }

    /**
     * Get the Heroicon name used on the selection card.
     */
    public function icon(): string
    {
        return match ($this) {
            self::SMA => 'academic-cap',
            self::S1 => 'book-open',
            self::FreshGraduate => 'sparkles',
            self::Worker => 'briefcase',
        };
    }
}
