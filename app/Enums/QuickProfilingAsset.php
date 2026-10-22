<?php

namespace App\Enums;

use App\Enums\Concerns\HasSelectOptions;
use Filament\Support\Contracts\HasLabel;

/**
 * The answer to the "apa yang sudah kamu punya?" question of Profiling Kilat.
 *
 * The None case is exclusive: it can never be combined with a real asset.
 */
enum QuickProfilingAsset: string implements HasLabel
{
    use HasSelectOptions;

    case LanguageScore = 'language_score';
    case EssayDraft = 'essay_draft';
    case Organization = 'organization';
    case Achievement = 'achievement';
    case None = 'none';

    public function getLabel(): string
    {
        return match ($this) {
            self::LanguageScore => 'Skor Bahasa',
            self::EssayDraft => 'Draf Esai',
            self::Organization => 'Pengalaman Organisasi',
            self::Achievement => 'Prestasi',
            self::None => 'Belum Ada',
        };
    }

    /**
     * Get the Heroicon name used on the selection card.
     */
    public function icon(): string
    {
        return match ($this) {
            self::LanguageScore => 'language',
            self::EssayDraft => 'document-text',
            self::Organization => 'users',
            self::Achievement => 'star',
            self::None => 'x-circle',
        };
    }

    /**
     * Determine whether the asset blocks every other asset from being picked.
     */
    public function isExclusive(): bool
    {
        return $this === self::None;
    }

    /**
     * Get the number of points this asset contributes to the readiness score.
     */
    public function points(): int
    {
        return match ($this) {
            self::LanguageScore => 12,
            self::EssayDraft => 10,
            self::Achievement => 10,
            self::Organization => 8,
            self::None => 0,
        };
    }
}
