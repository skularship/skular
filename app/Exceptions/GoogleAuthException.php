<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a Google account cannot be resolved into a mentee account.
 */
class GoogleAuthException extends RuntimeException {}
