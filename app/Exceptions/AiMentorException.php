<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when the AI Mentor chat cannot produce an answer so the UI can offer a retry.
 */
class AiMentorException extends RuntimeException {}
