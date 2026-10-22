<?php

namespace App\Enums;

use App\Enums\Concerns\HasSelectOptions;
use Filament\Support\Contracts\HasLabel;

/**
 * Input shape of an admin-configured brainstorming question.
 *
 * Only these three types are supported so the mentee UI, the backend
 * validation and the AI prompt always agree about the answer shape.
 */
enum BrainstormQuestionType: string implements HasLabel
{
    use HasSelectOptions;

    case SingleChoice = 'single_choice';
    case MultipleChoice = 'multiple_choice';
    case ShortText = 'short_text';

    public function getLabel(): string
    {
        return match ($this) {
            self::SingleChoice => 'Pilihan tunggal',
            self::MultipleChoice => 'Pilihan ganda',
            self::ShortText => 'Teks singkat',
        };
    }

    /**
     * Determine whether this question type is answered from a configured list.
     */
    public function hasOptions(): bool
    {
        return $this !== self::ShortText;
    }
}
