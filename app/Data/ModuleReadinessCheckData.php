<?php

namespace App\Data;

use Spatie\LaravelData\Data;

/**
 * A single completeness requirement of a curriculum module.
 */
class ModuleReadinessCheckData extends Data
{
    /**
     * @param  string  $key  Stable check identifier.
     * @param  string  $label  Checklist text for the admin.
     * @param  string  $hint  How to fix the check when it fails.
     * @param  bool  $passed  Whether the module satisfies the check.
     * @param  bool  $required  Whether a failure blocks publication.
     */
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
