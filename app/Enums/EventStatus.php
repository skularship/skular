<?php

namespace App\Enums;

use App\Enums\Concerns\HasSelectOptions;
use Filament\Support\Contracts\HasLabel;

/**
 * Publication state of an event (events.status). Only Published events are shown to mentees.
 */
enum EventStatus: string implements HasLabel
{
    use HasSelectOptions;

    case Draft = 'draft';
    case Published = 'published';
    case Cancelled = 'cancelled';

    /**
     * Get the Indonesian label.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Published => 'Terbit',
            self::Cancelled => 'Dibatalkan',
        };
    }
}
