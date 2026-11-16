<?php

namespace App\Enums;

/**
 * Where a Premium order stands: waiting for payment, paid, or ended without payment.
 */
enum OrderStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Expired = 'expired';

    /**
     * Get the Indonesian label shown to mentees.
     *
     * @return string Human readable label.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu pembayaran',
            self::Paid => 'Berhasil',
            self::Failed => 'Gagal',
            self::Expired => 'Kedaluwarsa',
        };
    }

    /**
     * Get the x-ui.pill variant used for this status.
     *
     * @return string One of the pill variants (warning, success, danger, neutral).
     */
    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Paid => 'success',
            self::Failed => 'danger',
            self::Expired => 'neutral',
        };
    }
}
