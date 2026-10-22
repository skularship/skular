<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when AI feedback generation fails so callers can fall back gracefully.
 */
class AiFeedbackException extends RuntimeException {}
