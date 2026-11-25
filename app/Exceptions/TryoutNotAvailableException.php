<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a mentee may not start a Try Out (draft, attempt limit reached,
 * Pro only, or no subtests yet). The message is shown to the mentee as is.
 */
class TryoutNotAvailableException extends RuntimeException {}
