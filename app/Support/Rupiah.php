<?php

namespace App\Support;

/**
 * Formats whole rupiah amounts the Indonesian way, e.g. Rp74.500.
 */
final class Rupiah
{
    /**
     * Format an amount as "Rp74.500".
     */
    public static function format(int $amount): string
    {
        return 'Rp'.number_format($amount, 0, ',', '.');
    }
}
