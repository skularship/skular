<?php

namespace App\Enums;

use App\Enums\Concerns\HasSelectOptions;
use Filament\Support\Contracts\HasLabel;

enum LocationScope: string implements HasLabel
{
    use HasSelectOptions;

    case DalamNegeri = 'dalam_negeri';
    case LuarNegeri = 'luar_negeri';
    case TunjanganSaja = 'Tunjangan saja';
    case Parsial = 'Parsial';
    case Penuh = 'Penuh';

    public function getLabel(): string
    {
        return match ($this) {
            self::DalamNegeri => 'Dalam Negeri',
            self::LuarNegeri => 'Luar Negeri',
            self::TunjanganSaja => 'Tunjangan Saja',
            self::Parsial => 'Parsial',
            self::Penuh => 'Penuh'
        };
    }
}
