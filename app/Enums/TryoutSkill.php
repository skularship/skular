<?php

namespace App\Enums;

use App\Enums\Concerns\HasSelectOptions;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * One subtest of a Try Out. Listening and Reading are objective (auto scored,
 * adaptive per question); Writing and Speaking are assessed by AI.
 */
enum TryoutSkill: string implements HasColor, HasLabel
{
    use HasSelectOptions;

    case Listening = 'listening';
    case Reading = 'reading';
    case Writing = 'writing';
    case Speaking = 'speaking';

    /**
     * Get the label shown to mentees and admins.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::Listening => 'Listening',
            self::Reading => 'Reading',
            self::Writing => 'Writing',
            self::Speaking => 'Speaking',
        };
    }

    /**
     * Get the Filament color of the skill badge.
     */
    public function getColor(): string
    {
        return match ($this) {
            self::Listening => 'info',
            self::Reading => 'success',
            self::Writing => 'warning',
            self::Speaking => 'danger',
        };
    }

    /**
     * Whether answers are checked against a key (no AI).
     */
    public function isObjective(): bool
    {
        return in_array($this, [self::Listening, self::Reading], true);
    }

    /**
     * Heroicon name used on cards and steppers.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Listening => 'speaker-wave',
            self::Reading => 'book-open',
            self::Writing => 'pencil-square',
            self::Speaking => 'microphone',
        };
    }

    /**
     * Default question count of a new section.
     */
    public function defaultQuestionCount(): int
    {
        return match ($this) {
            self::Listening, self::Reading => 40,
            self::Writing => 2,
            self::Speaking => 3,
        };
    }

    /**
     * Default duration in minutes of a new section.
     */
    public function defaultMinutes(): int
    {
        return match ($this) {
            self::Listening => 30,
            self::Reading, self::Writing => 60,
            self::Speaking => 14,
        };
    }

    /**
     * Name of one item of this skill shown to mentees ("Task 1", "Part 2", "Soal 3").
     */
    public function itemLabel(int $number): string
    {
        return match ($this) {
            self::Writing => 'Task '.$number,
            self::Speaking => 'Part '.$number,
            default => 'Soal '.$number,
        };
    }
}
