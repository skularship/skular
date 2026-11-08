<?php

namespace App\Enums;

/**
 * The label a mentee sees instead of the numeric AI score.
 *
 * The 0-100 score stays stored for admins and reporting; mentees only see
 * which band their work falls into, so they focus on the written feedback
 * instead of chasing a number.
 */
enum AiScoreLabel: string
{
    case SangatBaik = 'sangat_baik';
    case Baik = 'baik';
    case Cukup = 'cukup';
    case PerluDiperbaiki = 'perlu_diperbaiki';

    /**
     * Resolve the band of a 0-100 score.
     */
    public static function fromScore(int $score): self
    {
        return match (true) {
            $score >= 85 => self::SangatBaik,
            $score >= 70 => self::Baik,
            $score >= 50 => self::Cukup,
            default => self::PerluDiperbaiki,
        };
    }

    /**
     * Resolve the band of an optional score.
     */
    public static function tryFromScore(int|float|null $score): ?self
    {
        return $score === null ? null : self::fromScore((int) round($score));
    }

    /**
     * Get the label shown to mentees instead of the numeric score.
     *
     * @return string Sangat baik, Baik, Cukup or Perlu diperbaiki.
     */
    public function label(): string
    {
        return match ($this) {
            self::SangatBaik => 'Sangat baik',
            self::Baik => 'Baik',
            self::Cukup => 'Cukup',
            self::PerluDiperbaiki => 'Perlu diperbaiki',
        };
    }

    /**
     * The pill variant used to colour the label.
     */
    public function variant(): string
    {
        return match ($this) {
            self::SangatBaik, self::Baik => 'success',
            self::Cukup => 'info',
            self::PerluDiperbaiki => 'warning',
        };
    }

    /**
     * A short encouraging hint shown next to the label.
     */
    public function hint(): string
    {
        return match ($this) {
            self::SangatBaik => 'Jawabanmu sudah kuat. Pertahankan!',
            self::Baik => 'Sudah bagus, tinggal dipoles sedikit.',
            self::Cukup => 'Arahnya sudah benar, perkuat dengan saran di bawah.',
            self::PerluDiperbaiki => 'Ikuti saran perbaikan, lalu kirim ulang.',
        };
    }
}
