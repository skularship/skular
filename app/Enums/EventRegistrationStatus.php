<?php

namespace App\Enums;

use App\Enums\Concerns\HasSelectOptions;
use Filament\Support\Contracts\HasLabel;

/**
 * State of one mentee's seat at an event (event_registrations.status).
 */
enum EventRegistrationStatus: string implements HasLabel
{
    use HasSelectOptions;

    case Registered = 'registered';
    case Cancelled = 'cancelled';
    case Attended = 'attended';
    case NoShow = 'no_show';

    /**
     * Get the Indonesian label.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::Registered => 'Terdaftar',
            self::Cancelled => 'Batal',
            self::Attended => 'Hadir',
            self::NoShow => 'Tidak hadir',
        };
    }

    /**
     * Statuses that hold a seat (count towards the capacity).
     *
     * @return array<int, self>
     */
    public static function holdingSeat(): array
    {
        return [self::Registered, self::Attended, self::NoShow];
    }
}
