<?php

namespace App\Enums;

use App\Enums\Concerns\HasSelectOptions;
use Filament\Support\Contracts\HasLabel;

/**
 * Mode a mentee picks before starting a Try Out. Only the Listening audio
 * replay limit differs: practice allows audio_plays_practice, exam audio_plays_exam.
 */
enum TryoutMode: string implements HasLabel
{
    use HasSelectOptions;

    case Practice = 'practice';
    case Exam = 'exam';

    /**
     * Get the Indonesian label.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::Practice => 'Latihan',
            self::Exam => 'Ujian',
        };
    }

    /**
     * One-line explanation shown on the mode card.
     */
    public function description(): string
    {
        return match ($this) {
            self::Practice => 'Audio Listening bisa diputar maksimal 2 kali per bagian. Cocok untuk belajar.',
            self::Exam => 'Audio hanya diputar sekali, seperti tes asli. Cocok untuk mengukur kesiapan.',
        };
    }
}
