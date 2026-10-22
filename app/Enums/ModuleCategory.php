<?php

namespace App\Enums;

use App\Enums\Concerns\HasSelectOptions;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Curriculum classification of a learning module.
 *
 * - Inti: general material that applies to every scholarship.
 * - Template: material tailored to one specific scholarship.
 * - Perpustakaan: optional cross-scholarship library training.
 */
enum ModuleCategory: string implements HasColor, HasLabel
{
    use HasSelectOptions;

    case Inti = 'inti';
    case Template = 'template';
    case Perpustakaan = 'perpustakaan';

    public function getLabel(): string
    {
        return match ($this) {
            self::Inti => 'Inti',
            self::Template => 'Template Beasiswa',
            self::Perpustakaan => 'Perpustakaan',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Inti => 'success',
            self::Template => 'warning',
            self::Perpustakaan => 'info',
        };
    }

    /**
     * Determine whether modules of this category belong to a single scholarship.
     */
    public function requiresScholarship(): bool
    {
        return $this === self::Template;
    }

    /**
     * Get the values shown on the mentee learning roadmap.
     *
     * Library modules live in their own catalog, so they are excluded from the
     * scholarship preparation roadmap.
     *
     * @return array<int, string>
     */
    public static function roadmapValues(): array
    {
        return [self::Inti->value, self::Template->value];
    }
}
