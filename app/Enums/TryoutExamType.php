<?php

namespace App\Enums;

use App\Enums\Concerns\HasSelectOptions;
use Filament\Support\Contracts\HasLabel;

/**
 * Exam a Try Out simulates. IELTS is live in v1; SAT exists so the schema
 * stays generic and is hidden from admins until config('tryout.enabled_exam_types') lists it.
 */
enum TryoutExamType: string implements HasLabel
{
    use HasSelectOptions;

    case Ielts = 'ielts';
    case Sat = 'sat';

    /**
     * Get the label shown to mentees and admins.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::Ielts => 'IELTS Academic',
            self::Sat => 'SAT',
        };
    }

    /**
     * Whether admins may pick and publish this exam type right now.
     */
    public function isEnabled(): bool
    {
        $enabled = config('tryout.enabled_exam_types', ['ielts']);

        return is_array($enabled) && in_array($this->value, $enabled, true);
    }

    /**
     * Value => label map of the exam types admins may pick.
     *
     * @return array<string, string>
     */
    public static function enabledOptions(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            if ($case->isEnabled()) {
                $options[$case->value] = $case->getLabel();
            }
        }

        return $options;
    }

    /**
     * The subtests this exam requires, in the order they are taken.
     *
     * @return array<int, TryoutSkill>
     */
    public function requiredSkills(): array
    {
        return match ($this) {
            self::Ielts => [TryoutSkill::Listening, TryoutSkill::Reading, TryoutSkill::Writing, TryoutSkill::Speaking],
            self::Sat => [],
        };
    }

    /**
     * Name of the score shown to mentees ("band" for IELTS).
     */
    public function scoreName(): string
    {
        return match ($this) {
            self::Ielts => 'band',
            self::Sat => 'skor',
        };
    }
}
