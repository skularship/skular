<?php

namespace App\Enums;

use App\Enums\Concerns\HasSelectOptions;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Review state of a mentee's "Informasi Tidak Akurat?" report, from new to accepted or rejected.
 */
enum ReportStatus: string implements HasColor, HasLabel
{
    use HasSelectOptions;

    case New = 'new';
    case InReview = 'in_review';
    case Accepted = 'accepted';
    case Rejected = 'rejected';

    /**
     * Get the Indonesian label shown in the Filament admin.
     *
     * @return string Human readable label.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::New => 'Baru',
            self::InReview => 'Ditinjau',
            self::Accepted => 'Diterima',
            self::Rejected => 'Ditolak',
        };
    }

    /**
     * Get the Filament color used for badges of this case.
     *
     * @return string Filament color name (gray, info, success, warning, danger, ...).
     */
    public function getColor(): string
    {
        return match ($this) {
            self::New => 'warning',
            self::InReview => 'info',
            self::Accepted => 'success',
            self::Rejected => 'gray',
        };
    }

    /**
     * Determine whether the report still waits for a decision.
     */
    public function isOpen(): bool
    {
        return in_array($this, self::openCases(), true);
    }

    /**
     * Get the statuses that still wait for a decision.
     *
     * @return array<int, self>
     */
    public static function openCases(): array
    {
        return [self::New, self::InReview];
    }
}
