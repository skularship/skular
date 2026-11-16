<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A payment step failed in a way the mentee can understand; the message is safe to show in the UI.
 */
class PaymentException extends RuntimeException
{
    /**
     * The payment gateway could not create a payment session.
     */
    public static function gatewayUnavailable(): self
    {
        return new self('Pembayaran belum bisa dimulai. Coba lagi sebentar lagi atau hubungi Bantuan.');
    }

    /**
     * The chosen product does not exist or is not for sale.
     */
    public static function productUnavailable(): self
    {
        return new self('Paket ini tidak tersedia.');
    }

    /**
     * A Midtrans notification did not carry a valid signature.
     */
    public static function invalidSignature(): self
    {
        return new self('Signature notifikasi tidak valid.');
    }

    /**
     * The amount in a Midtrans notification differs from the order amount.
     */
    public static function amountMismatch(): self
    {
        return new self('Jumlah pembayaran tidak sama dengan pesanan.');
    }
}
