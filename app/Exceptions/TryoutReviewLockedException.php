<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a Gratis mentee asks for the Pro review (key and explanation).
 */
class TryoutReviewLockedException extends RuntimeException
{
    /**
     * Build the exception with the default friendly message.
     */
    public function __construct(string $message = 'Kunci jawaban dan pembahasan tersedia untuk paket Pro.')
    {
        parent::__construct($message);
    }
}
