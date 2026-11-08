<?php

namespace App\Enums;

use App\Enums\Concerns\HasSelectOptions;
use Filament\Support\Contracts\HasLabel;

/**
 * Scholarship location/funding scope. Profiling only offers the study destinations
 * (dalam_negeri, luar_negeri); the funding values (Tunjangan saja, Parsial, Penuh)
 * are scholarship attributes and must never be stored as a mentee answer.
 */
enum LocationScope: string implements HasLabel
{
    use HasSelectOptions;

    case DalamNegeri = 'dalam_negeri';
    case LuarNegeri = 'luar_negeri';
    case TunjanganSaja = 'Tunjangan saja';
    case Parsial = 'Parsial';
    case Penuh = 'Penuh';

    /**
     * Get the Indonesian label shown to mentees and in the Filament admin.
     *
     * @return string Human readable label.
     */
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

    /**
     * Get the study destinations a mentee can choose while profiling.
     *
     * Funding categories belong to scholarship benefits, never to the mentee's
     * location preference, so they are deliberately left out of this list.
     *
     * @return array<int, self>
     */
    public static function studyDestinations(): array
    {
        return [self::DalamNegeri, self::LuarNegeri];
    }

    /**
     * Get the profiling choices as a value => label map.
     *
     * @return array<string, string>
     */
    public static function studyDestinationOptions(): array
    {
        $options = [];

        foreach (self::studyDestinations() as $destination) {
            $options[$destination->value] = $destination->getLabel();
        }

        return $options;
    }

    /**
     * Determine whether this scope describes where the study takes place.
     */
    public function isStudyDestination(): bool
    {
        return in_array($this, self::studyDestinations(), true);
    }
}
