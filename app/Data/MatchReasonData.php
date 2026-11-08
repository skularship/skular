<?php

namespace App\Data;

use Spatie\LaravelData\Data;

/**
 * A single, human readable eligibility explanation shown on a scholarship card.
 */
class MatchReasonData extends Data
{
    /**
     * @param  string  $label  Short criterion name, e.g. the education level.
     * @param  string  $detail  Explanation shown under the label.
     * @param  bool  $satisfied  Whether the mentee meets the criterion.
     * @param  bool  $informational  True when the line is context only and never blocks eligibility.
     */
    public function __construct(
        public string $label,
        public string $detail,
        public bool $satisfied,
        public bool $informational = false,
    ) {}
}
