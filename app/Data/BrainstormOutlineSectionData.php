<?php

namespace App\Data;

use Spatie\LaravelData\Data;

/**
 * A portion of the AI draft outline that maps to one practice form field.
 */
class BrainstormOutlineSectionData extends Data
{
    /**
     * @param  array<int, string>  $points
     */
    public function __construct(
        public string $field,
        public string $guidance,
        public array $points = [],
    ) {}
}
