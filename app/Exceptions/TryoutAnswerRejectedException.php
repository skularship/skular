<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when an answer does not belong to the active question (already
 * answered in another tab, or a tampered code). The page reloads the question.
 */
class TryoutAnswerRejectedException extends RuntimeException
{
    /**
     * Build the exception with the default friendly message.
     */
    public function __construct(string $message = 'Soal ini sudah dijawab di tab lain. Kami muat ulang soal yang aktif ya.')
    {
        parent::__construct($message);
    }
}
