<?php

namespace App\Data;

use Spatie\LaravelData\Data;

/**
 * Structured feedback returned by the AI learning evaluator.
 */
class AiFeedbackData extends Data
{
    /**
     * @param  array<int, string>  $strengths
     * @param  array<int, string>  $improvements
     * @param  array<int, string>  $next_steps
     */
    public function __construct(
        public int $score,
        public array $strengths,
        public array $improvements,
        public string $feedback,
        public array $next_steps,
    ) {}

    /**
     * Get the score clamped into the 0-100 range.
     */
    public function normalizedScore(): int
    {
        return max(0, min(100, $this->score));
    }
}
