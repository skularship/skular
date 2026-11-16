<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Raised when a vault action is not allowed. The message is safe to show the mentee.
 */
class VaultException extends RuntimeException
{
    /**
     * The mentee is on Gratis or Pro has ended.
     */
    public static function proRequired(): self
    {
        return new self(__('Vault dokumen hanya untuk paket Pro.'));
    }

    /**
     * The vault already holds the maximum number of files.
     */
    public static function limitReached(int $max): self
    {
        return new self(__('Vault sudah berisi :max dokumen. Hapus satu dulu sebelum menambah.', ['max' => $max]));
    }
}
