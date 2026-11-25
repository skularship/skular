<?php

namespace App\Data;

use Spatie\LaravelData\Data;

/**
 * Result card of one subtest on the result page.
 */
class TryoutSectionResultData extends Data
{
    /**
     * @param  string  $skill  TryoutSkill value.
     * @param  string  $label  Subtest name.
     * @param  int|null  $raw_score  Correct answers (L/R).
     * @param  int|null  $max_score  Question count (L/R).
     * @param  float|null  $band  Band, null while AI assessment is pending.
     * @param  int|null  $final_level  Last adaptive level (L/R).
     * @param  string|null  $ai_status  AiFeedbackStatus value of the W/S assessment.
     * @param  array<int, CriterionBandData>  $criteria  W/S criteria.
     * @param  array<int, array{label: string, band: float|null, status: string|null, answer_id: int, can_retry: bool}>  $items  Writing tasks / Speaking parts.
     * @param  string  $summary  AI summary (W/S).
     * @param  bool  $needs_assessment  Something is still waiting for the AI.
     * @param  bool  $can_retry  A failed assessment may be retried.
     */
    public function __construct(
        public string $skill,
        public string $label,
        public ?int $raw_score,
        public ?int $max_score,
        public ?float $band,
        public ?int $final_level,
        public ?string $ai_status,
        public array $criteria = [],
        public array $items = [],
        public string $summary = '',
        public bool $needs_assessment = false,
        public bool $can_retry = false,
    ) {}
}
