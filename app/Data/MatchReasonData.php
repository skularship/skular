<?php

namespace App\Data;

use Spatie\LaravelData\Data;

/**
 * A single, human readable eligibility explanation shown on a scholarship card.
 */
class MatchReasonData extends Data
{
    public function __construct(
        public string $label,
        public string $detail,
        public bool $satisfied,
        public bool $informational = false,
    ) {}
}
