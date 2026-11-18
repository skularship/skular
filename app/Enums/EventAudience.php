<?php

namespace App\Enums;

use App\Enums\Concerns\HasSelectOptions;
use Filament\Support\Contracts\HasLabel;

/**
 * Who may see and join an event (events.audience): every mentee, only Pro
 * mentees, or only mentees carrying the event's invite tag (users.tags).
 */
enum EventAudience: string implements HasLabel
{
    use HasSelectOptions;

    case Semua = 'semua';
    case Pro = 'pro';
    case Undangan = 'undangan';

    /**
     * Get the Indonesian label.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::Semua => 'Semua mentee',
            self::Pro => 'Khusus Pro',
            self::Undangan => 'Undangan (tag mentee)',
        };
    }
}
