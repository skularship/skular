<?php

namespace App\Enums;

use App\Enums\Concerns\HasSelectOptions;
use Filament\Support\Contracts\HasLabel;

/**
 * Kind of Skular event (events.type): webinar, intensive class, or personal mentoring.
 */
enum EventType: string implements HasLabel
{
    use HasSelectOptions;

    case Webinar = 'webinar';
    case KelasIntensif = 'kelas_intensif';
    case MentoringPersonal = 'mentoring_personal';

    /**
     * Get the Indonesian label.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::Webinar => 'Webinar',
            self::KelasIntensif => 'Kelas Intensif',
            self::MentoringPersonal => 'Mentoring Personal',
        };
    }

    /**
     * Get the heroicon shown on the event card.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Webinar => 'video-camera',
            self::KelasIntensif => 'academic-cap',
            self::MentoringPersonal => 'chat-bubble-left-right',
        };
    }
}
