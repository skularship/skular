<?php

namespace App\Enums;

use App\Enums\Concerns\HasSelectOptions;
use Filament\Support\Contracts\HasLabel;

enum LocationScope: string implements HasLabel
{
    use HasSelectOptions;

    case DalamNegeri = 'dalam_negeri';
    case LuarNegeri = 'luar_negeri';

    public function getLabel(): string
    {
        return match ($this) {
            self::DalamNegeri => 'Dalam Negeri',
            self::LuarNegeri => 'Luar Negeri',
        };
    }
}
