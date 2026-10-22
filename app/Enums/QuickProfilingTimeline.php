<?php

namespace App\Enums;

use App\Enums\Concerns\HasSelectOptions;
use Filament\Support\Contracts\HasLabel;

/**
 * The answer to the "kapan target daftar?" question of Profiling Kilat.
 */
enum QuickProfilingTimeline: string implements HasLabel
{
    use HasSelectOptions;

    case UnderThreeMonths = 'under_3_months';
    case ThreeToSixMonths = '3_6_months';
    case SixToTwelveMonths = '6_12_months';
    case OverTwelveMonths = 'over_12_months';

    public function getLabel(): string
    {
        return match ($this) {
            self::UnderThreeMonths => 'Kurang dari 3 bulan',
            self::ThreeToSixMonths => '3–6 bulan',
            self::SixToTwelveMonths => '6–12 bulan',
            self::OverTwelveMonths => 'Lebih dari 12 bulan',
        };
    }

    /**
     * Get the Heroicon name used on the selection card.
     */
    public function icon(): string
    {
        return match ($this) {
            self::UnderThreeMonths => 'bolt',
            self::ThreeToSixMonths => 'calendar-days',
            self::SixToTwelveMonths => 'calendar',
            self::OverTwelveMonths => 'clock',
        };
    }

    /**
     * Get the number of points this horizon contributes to the readiness score.
     *
     * A longer runway buys more preparation room, but a short timeline never
     * scores zero: a rushed applicant can still be well prepared.
     */
    public function points(): int
    {
        return match ($this) {
            self::UnderThreeMonths => 10,
            self::ThreeToSixMonths => 16,
            self::SixToTwelveMonths => 21,
            self::OverTwelveMonths => 25,
        };
    }
}
