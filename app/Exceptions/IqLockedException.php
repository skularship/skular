<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a mentee without paid access asks for the Tes IQ score, key,
 * explanation, material or mentor.
 */
class IqLockedException extends RuntimeException
{
    /**
     * Build the exception with the default friendly message.
     */
    public function __construct(string $message = 'Skor, pembahasan, materi, dan mentor AI Tes IQ terbuka setelah kamu membuka akses.')
    {
        parent::__construct($message);
    }
}
