<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a mentee may not start a Tes IQ (no published test or no
 * active questions yet). The message is shown to the mentee as is.
 */
class IqNotAvailableException extends RuntimeException {}
