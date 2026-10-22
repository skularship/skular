<?php

namespace App\Data;

use Spatie\LaravelData\Data;

/**
 * A single completeness requirement of a curriculum module.
 */
class ModuleReadinessCheckData extends Data
{
    public function __construct(
        public string $key,
        public string $label,
        public string $hint,
        public bool $passed,
        public bool $required = true,
    ) {}

    /**
     * Determine whether the check blocks publication.
     */
    public function isBlocking(): bool
    {
        return $this->required && ! $this->passed;
    }
}
