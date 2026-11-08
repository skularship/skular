<?php

namespace App\Enums;

use App\Enums\Concerns\HasSelectOptions;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Progress of a scholarship application in the "Beasiswa Saya" tracker, from selected to result reported.
 */
enum ApplicationStatus: string implements HasColor, HasLabel
{
    use HasSelectOptions;

    case Selected = 'selected';
    case InProgress = 'in_progress';
    case Submitted = 'submitted';
    case ResultReported = 'result_reported';

    /**
     * Get the Indonesian label shown to mentees and in the Filament admin.
     *
     * @return string Human readable label.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::Selected => 'Dipilih',
            self::InProgress => 'Persiapan',
            self::Submitted => 'Sudah Submit',
            self::ResultReported => 'Hasil Dilaporkan',
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
            self::Selected => 'gray',
            self::InProgress => 'info',
            self::Submitted => 'warning',
            self::ResultReported => 'success',
        };
    }
}
