<?php

namespace App\Enums;

use App\Enums\Concerns\HasSelectOptions;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ModuleType: string implements HasColor, HasLabel
{
    use HasSelectOptions;

    case Reading = 'reading';
    case Video = 'video';
    case Practice = 'practice';
    case Mixed = 'mixed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Reading => 'Materi Bacaan',
            self::Video => 'Video',
            self::Practice => 'Praktik',
            self::Mixed => 'Campuran',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Reading => 'info',
            self::Video => 'purple',
            self::Practice => 'warning',
            self::Mixed => 'gray',
        };
    }
}
