<?php

namespace App\Enums;

use App\Enums\Concerns\HasSelectOptions;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Lifecycle of one subtest inside an attempt: pending -> in_progress ->
 * submitted (closed by the mentee or the deadline) -> scored. Stored values; never rename.
 */
enum TryoutSectionStatus: string implements HasColor, HasLabel
{
    use HasSelectOptions;

    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Submitted = 'submitted';
    case Scored = 'scored';

    /**
     * Get the Indonesian label.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Belum mulai',
            self::InProgress => 'Dikerjakan',
            self::Submitted => 'Terkirim',
            self::Scored => 'Dinilai',
        };
    }

    /**
     * Get the Filament badge color.
     */
    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::InProgress => 'warning',
            self::Submitted => 'info',
            self::Scored => 'success',
        };
    }

    /**
     * Whether the mentee can no longer answer in this subtest.
     */
    public function isClosed(): bool
    {
        return in_array($this, [self::Submitted, self::Scored], true);
    }
}
