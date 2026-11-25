<?php

namespace App\Data;

use Spatie\LaravelData\Data;

/**
 * Everything the free result page shows: overall band, one card per
 * subtest and whether the Pro review is open for this mentee.
 */
class TryoutResultData extends Data
{
    /**
     * @param  int  $attempt_id  The attempt.
     * @param  string  $tryout_title  Try Out title.
     * @param  float|null  $overall_band  Null until every subtest has a band.
     * @param  array<int, TryoutSectionResultData>  $sections  In subtest order.
     * @param  bool  $is_estimate  Always true: practice estimate, not an official score.
     * @param  bool  $can_review  Pro review (key + explanation) is open.
     * @param  string|null  $strongest  Label of the strongest subtest.
     * @param  string|null  $weakest  Label of the subtest that most needs practice.
     * @param  bool  $needs_assessment  At least one AI assessment is still pending.
     */
    public function __construct(
        public int $attempt_id,
        public string $tryout_title,
        public ?float $overall_band,
        public array $sections,
        public bool $is_estimate,
        public bool $can_review,
        public ?string $strongest = null,
        public ?string $weakest = null,
        public bool $needs_assessment = false,
    ) {}
}
