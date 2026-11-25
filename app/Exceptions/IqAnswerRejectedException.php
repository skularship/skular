<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a Tes IQ answer is refused: the attempt is closed, the server
 * deadline passed, or the question or letter is not part of the attempt.
 * The message is shown to the mentee as is.
 */
class IqAnswerRejectedException extends RuntimeException {}
