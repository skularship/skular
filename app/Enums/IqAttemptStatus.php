<?php

namespace App\Enums;

use App\Enums\Concerns\HasSelectOptions;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Lifecycle of one Tes IQ attempt: in_progress -> completed (mentee pressed
 * "Kirim") or expired (the server deadline passed; answers sent in time
 * still count). Stored values; never rename.
 */
enum IqAttemptStatus: string implements HasColor, HasLabel
{
    use HasSelectOptions;

    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Expired = 'expired';

    /**
     * Get the Indonesian label.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::InProgress => 'Berjalan',
            self::Completed => 'Selesai',
            self::Expired => 'Waktu habis',
        };
    }

    /**
     * Get the Filament badge color.
     */
    public function getColor(): string
    {
        return match ($this) {
            self::InProgress => 'warning',
            self::Completed => 'success',
            self::Expired => 'gray',
        };
    }

    /**
     * Whether the attempt is closed and scored.
     */
    public function isClosed(): bool
    {
        return $this !== self::InProgress;
    }
}
