<?php

namespace App\Data;

use Spatie\LaravelData\Data;

/**
 * AI assessment of a Writing task or of the whole Speaking subtest:
 * four criteria, the rounded band and a short summary.
 */
class TryoutAssessmentData extends Data
{
    /**
     * @param  array<int, CriterionBandData>  $criteria  Four criteria in rubric order.
     * @param  float  $band  Rounded band (0..9, steps of 0.5).
     * @param  string  $summary  Two or three sentences in Indonesian.
     * @param  bool  $pronunciation_is_estimate  Speaking: pronunciation inferred from a transcript.
     */
    public function __construct(
        public array $criteria,
        public float $band,
        public string $summary = '',
        public bool $pronunciation_is_estimate = false,
    ) {}

    /**
     * Read a stored assessment array back into the DTO.
     *
     * @param  array<string, mixed>|null  $stored
     */
    public static function fromStored(?array $stored): ?self
    {
        if ($stored === null || ! is_numeric($stored['band'] ?? null)) {
            return null;
        }

        return new self(
            criteria: CriterionBandData::listFrom($stored['criteria'] ?? null),
            band: (float) $stored['band'],
            summary: is_string($stored['summary'] ?? null) ? $stored['summary'] : '',
            pronunciation_is_estimate: (bool) ($stored['pronunciation_is_estimate'] ?? false),
        );
    }
}
