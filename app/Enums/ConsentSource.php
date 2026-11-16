<?php

namespace App\Enums;

/**
 * Where a mentee accepted a legal document: the sign-up form, the first
 * Google sign-in, the Premium checkout, or the re-consent screen after a
 * document version changed.
 */
enum ConsentSource: string
{
    case Register = 'register';
    case Google = 'google';
    case Checkout = 'checkout';
    case Reconsent = 'reconsent';

    /**
     * Indonesian label for the admin panel.
     */
    public function label(): string
    {
        return match ($this) {
            self::Register => 'Daftar akun',
            self::Google => 'Login Google pertama',
            self::Checkout => 'Pembayaran',
            self::Reconsent => 'Persetujuan ulang',
        };
    }
}
