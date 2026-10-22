<?php

namespace App\Data;

use Spatie\LaravelData\Data;

/**
 * One step of the mentee learning loop as the curriculum team sees it.
 */
class ModuleOutlineStepData extends Data
{
    /**
     * @param  array<int, string>  $items  what this step actually shows the mentee
     */
    public function __construct(
        public string $key,
        public string $label,
        public string $purpose,
        public bool $present,
        public array $items = [],
    ) {}
}
