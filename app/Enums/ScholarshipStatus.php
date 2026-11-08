<?php

namespace App\Enums;

use App\Enums\Concerns\HasSelectOptions;
use Carbon\CarbonImmutable;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Admin status of a scholarship. Only publicly visible statuses are shown to mentees; drafts and archived scholarships return 404.
 */
enum ScholarshipStatus: string implements HasColor, HasLabel
{
    use HasSelectOptions;

    case Draft = 'draft';
    case Active = 'active';
    case Closed = 'closed';
    case Archived = 'archived';

    /**
     * Get the Indonesian label shown to mentees and in the Filament admin.
     *
     * @return string Human readable label.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Active => 'Aktif',
            self::Closed => 'Ditutup',
            self::Archived => 'Arsip',
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
            self::Draft => 'gray',
            self::Active => 'success',
            self::Closed => 'warning',
            self::Archived => 'gray',
        };
    }

    /**
     * Determine whether the scholarship is currently open for registration.
     */
    public function isOpen(?CarbonImmutable $closing_date = null): bool
    {
        if ($this !== self::Active) {
            return false;
        }

        return $closing_date === null || $closing_date->endOfDay()->isFuture();
    }

    /**
     * Get the statuses visible to mentees.
     *
     * @return array<int, self>
     */
    public static function publiclyVisible(): array
    {
        return [self::Active, self::Closed];
    }
}
