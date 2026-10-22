<?php

namespace App\Enums;

use App\Enums\Concerns\HasSelectOptions;
use Filament\Support\Contracts\HasLabel;

/**
 * The answer to the weekly preparation time question of Profiling Kilat.
 */
enum QuickProfilingWeeklyTime: string implements HasLabel
{
    use HasSelectOptions;

    case UnderTwoHours = 'under_2_hours';
    case TwoToFiveHours = '2_5_hours';
    case OverFiveHours = 'over_5_hours';

    public function getLabel(): string
    {
        return match ($this) {
            self::UnderTwoHours => 'Kurang dari 2 jam',
            self::TwoToFiveHours => '2–5 jam',
            self::OverFiveHours => 'Lebih dari 5 jam',
        };
    }

    /**
     * Get the Heroicon name used on the selection card.
     */
    public function icon(): string
    {
        return match ($this) {
            self::UnderTwoHours => 'clock',
            self::TwoToFiveHours => 'calendar-days',
            self::OverFiveHours => 'arrow-trending-up',
        };
    }

    /**
     * Get the number of points this allocation contributes to the readiness score.
     */
    public function points(): int
    {
        return match ($this) {
            self::UnderTwoHours => 6,
            self::TwoToFiveHours => 17,
            self::OverFiveHours => 25,
        };
    }
}
