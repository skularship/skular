<?php

namespace App\Enums;

use App\Enums\Concerns\HasSelectOptions;
use Filament\Support\Contracts\HasLabel;

/**
 * The readiness band a Profiling Kilat score falls into.
 */
enum ReadinessCategory: string implements HasLabel
{
    use HasSelectOptions;

    case NeedsPreparation = 'needs_more_preparation';
    case BuildingReadiness = 'building_readiness';
    case StrongFoundation = 'strong_foundation';

    public function getLabel(): string
    {
        return match ($this) {
            self::NeedsPreparation => 'Perlu Persiapan Lebih Lanjut',
            self::BuildingReadiness => 'Sedang Membangun Kesiapan',
            self::StrongFoundation => 'Modal Kesiapan Sudah Baik',
        };
    }

    /**
     * Resolve the band for a 0-100 readiness score.
     */
    public static function forScore(int $score): self
    {
        return match (true) {
            $score >= 70 => self::StrongFoundation,
            $score >= 40 => self::BuildingReadiness,
            default => self::NeedsPreparation,
        };
    }

    /**
     * Get the dynamic headline shown above the result.
     */
    public function headline(): string
    {
        return match ($this) {
            self::NeedsPreparation => 'Persiapanmu Sudah Dimulai!',
            self::BuildingReadiness => 'Saatnya Memperkuat Modal Beasiswamu',
            self::StrongFoundation => 'Kamu Punya Modal, Sekarang Susun Strateginya',
        };
    }

    /**
     * Get the supporting sentence that explains the headline.
     */
    public function subheadline(): string
    {
        return match ($this) {
            self::NeedsPreparation => 'Kamu ada di titik awal. Dengan langkah kecil yang konsisten, modal beasiswamu bisa tumbuh dari sekarang.',
            self::BuildingReadiness => 'Sebagian modal sudah kamu punya. Menutup celah utama akan mempercepat kesiapanmu.',
            self::StrongFoundation => 'Modal kesiapanmu sudah baik. Saatnya menyusun strategi supaya setiap langkah terarah ke targetmu.',
        };
    }

    /**
     * Get the Heroicon name that represents the band.
     */
    public function icon(): string
    {
        return match ($this) {
            self::NeedsPreparation => 'light-bulb',
            self::BuildingReadiness => 'arrow-trending-up',
            self::StrongFoundation => 'check-badge',
        };
    }
}
