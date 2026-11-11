<?php

namespace App\Enums;

use App\Enums\Concerns\HasSelectOptions;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Handling state of a "Bantuan" ticket, from new to resolved or closed.
 */
enum SupportTicketStatus: string implements HasColor, HasLabel
{
    use HasSelectOptions;

    case New = 'new';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Closed = 'closed';

    /**
     * Get the Indonesian label shown to mentees and in the Filament admin.
     *
     * @return string Human readable label.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::New => 'Diterima',
            self::InProgress => 'Sedang ditangani',
            self::Resolved => 'Selesai',
            self::Closed => 'Ditutup',
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
            self::InProgress => 'info',
            self::Resolved => 'success',
            self::Closed => 'gray',
        };
    }

    /**
     * Get the pill variant used on the mentee "Bantuan" page.
     */
    public function pillVariant(): string
    {
        return match ($this) {
            self::New => 'warning',
            self::InProgress => 'info',
            self::Resolved => 'success',
            self::Closed => 'neutral',
        };
    }

    /**
     * Determine whether the ticket still waits for the team.
     */
    public function isOpen(): bool
    {
        return in_array($this, [self::New, self::InProgress], true);
    }
}
