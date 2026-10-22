<?php

namespace App\Data;

use App\Enums\MatchTier;
use Spatie\LaravelData\Data;

/**
 * A scholarship bundled with its match score and transparent eligibility reasons.
 */
class ScholarshipMatchData extends Data
{
    /**
     * @param  array<int, MatchReasonData>  $reasons
     */
    public function __construct(
        public ScholarshipData $scholarship,
        public int $score,
        public MatchTier $tier,
        public array $reasons,
    ) {}

    /**
     * Get only the eligibility points that passed.
     *
     * @return array<int, MatchReasonData>
     */
    public function passedReasons(): array
    {
        return array_values(array_filter(
            $this->reasons,
            fn (MatchReasonData $reason): bool => $reason->satisfied && ! $reason->informational,
        ));
    }

    /**
     * Get only the eligibility points that were not met.
     *
     * @return array<int, MatchReasonData>
     */
    public function warnings(): array
    {
        return array_values(array_filter(
            $this->reasons,
            fn (MatchReasonData $reason): bool => ! $reason->satisfied,
        ));
    }
}
