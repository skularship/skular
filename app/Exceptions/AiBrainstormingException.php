<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when AI brainstorming fails or returns a payload the app rejects.
 */
class AiBrainstormingException extends RuntimeException {}
