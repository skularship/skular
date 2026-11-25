<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when an answer arrives after the subtest deadline (plus grace).
 * The subtest is closed before this is thrown.
 */
class TryoutSectionExpiredException extends RuntimeException
{
    /**
     * Build the exception with the default friendly message.
     */
    public function __construct(string $message = 'Waktu subtes ini sudah habis. Jawabanmu yang sudah terkirim tetap tersimpan.')
    {
        parent::__construct($message);
    }
}
