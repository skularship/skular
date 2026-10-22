<?php

namespace App\Enums;

use App\Enums\Concerns\HasSelectOptions;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ApplicationStatus: string implements HasColor, HasLabel
{
    use HasSelectOptions;

    case Selected = 'selected';
    case InProgress = 'in_progress';
    case Submitted = 'submitted';
    case ResultReported = 'result_reported';

    public function getLabel(): string
    {
        return match ($this) {
            self::Selected => 'Dipilih',
            self::InProgress => 'Persiapan',
            self::Submitted => 'Sudah Submit',
            self::ResultReported => 'Hasil Dilaporkan',
        };
    }

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
