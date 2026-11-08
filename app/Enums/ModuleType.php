<?php

namespace App\Enums;

use App\Enums\Concerns\HasSelectOptions;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Presentation format of a module (legacy field modules.type). Not the same as
 * ModuleCategory (inti/template/perpustakaan) or ContentStatus (draft/published).
 */
enum ModuleType: string implements HasColor, HasLabel
{
    use HasSelectOptions;

    case Reading = 'reading';
    case Video = 'video';
    case Practice = 'practice';
    case Mixed = 'mixed';

    /**
     * Get the Indonesian label shown to mentees and in the Filament admin.
     *
     * @return string Human readable label.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::Reading => 'Materi Bacaan',
            self::Video => 'Video',
            self::Practice => 'Praktik',
            self::Mixed => 'Campuran',
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
            self::Reading => 'info',
            self::Video => 'purple',
            self::Practice => 'warning',
            self::Mixed => 'gray',
        };
    }
}
