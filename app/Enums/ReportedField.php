<?php

namespace App\Enums;

use App\Enums\Concerns\HasSelectOptions;
use App\Models\Scholarship;
use Filament\Support\Contracts\HasLabel;

/**
 * The part of a scholarship detail page a mentee reports as inaccurate ("Informasi Tidak Akurat?").
 */
enum ReportedField: string implements HasLabel
{
    use HasSelectOptions;

    case OpeningDate = 'opening_date';
    case ClosingDate = 'closing_date';
    case Requirements = 'requirements';
    case Quota = 'quota';
    case Benefits = 'benefits';
    case Link = 'link';
    case Other = 'other';

    /**
     * Get the Indonesian label shown to mentees and in the Filament admin.
     *
     * @return string Human readable label.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::OpeningDate => 'Tanggal buka',
            self::ClosingDate => 'Deadline',
            self::Requirements => 'Persyaratan',
            self::Quota => 'Kuota',
            self::Benefits => 'Benefit',
            self::Link => 'Tautan',
            self::Other => 'Lainnya',
        };
    }

    /**
     * Describe what the scholarship currently stores for this field, for the admin review screen.
     *
     * @param  Scholarship  $scholarship  The reported scholarship.
     * @return string Plain-text current value, or "—" when nothing is stored.
     */
    public function currentValue(Scholarship $scholarship): string
    {
        $value = match ($this) {
            self::OpeningDate => $scholarship->opening_date?->translatedFormat('d M Y'),
            self::ClosingDate => $scholarship->closing_date?->translatedFormat('d M Y'),
            self::Requirements => implode(' · ', array_filter([
                $scholarship->minimumGpa() === null ? null : 'IPK min '.number_format($scholarship->minimumGpa(), 2),
                $scholarship->maximum_age === null ? null : 'Usia maks '.$scholarship->maximum_age,
                $scholarship->sktm_require ? 'Wajib SKTM/KHI' : null,
            ])),
            self::Quota => $scholarship->estimated_quota === null ? null : (string) $scholarship->estimated_quota,
            self::Benefits => implode(' · ', $scholarship->keyBenefits()),
            self::Link => $scholarship->link,
            self::Other => null,
        };

        return filled($value) ? (string) $value : '—';
    }
}
