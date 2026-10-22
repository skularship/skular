<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when the central AI provider or model configuration is missing.
 */
class AiConfigurationException extends RuntimeException {}
