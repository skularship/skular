<?php

namespace App\Enums;

use App\Enums\Concerns\HasSelectOptions;
use Filament\Support\Contracts\HasLabel;

/**
 * "Bagian mana yang paling menyulitkan?" in the end-of-module feedback.
 * Values are stored in module_feedbacks.difficulty.
 */
enum FeedbackDifficulty: string implements HasLabel
{
    use HasSelectOptions;

    case Materi = 'materi';
    case QuickQuestion = 'quick_question';
    case Praktik = 'praktik';
    case PenilaianAi = 'penilaian_ai';
    case AiMentor = 'ai_mentor';
    case AlurWebsite = 'alur_website';
    case TidakAda = 'tidak_ada';

    /**
     * Get the Indonesian chip label.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::Materi => 'Materi',
            self::QuickQuestion => 'Quick Question',
            self::Praktik => 'Praktik',
            self::PenilaianAi => 'Penilaian AI',
            self::AiMentor => 'AI Mentor',
            self::AlurWebsite => 'Alur website',
            self::TidakAda => 'Tidak ada',
        };
    }
}
