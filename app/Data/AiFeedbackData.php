<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;

/**
 * Structured feedback returned by the AI learning evaluator.
 *
 * Each list item is a FeedbackPointData ({point, explanation}); toArray()
 * stores that shape in user_practice_responses.ai_feedback. The constructor
 * also accepts legacy plain strings and raw arrays and normalises them.
 */
class AiFeedbackData extends Data
{
    /**
     * What the answer already does well.
     *
     * @var array<int, FeedbackPointData>
     */
    #[DataCollectionOf(FeedbackPointData::class)]
    public array $strengths;

    /**
     * What to fix.
     *
     * @var array<int, FeedbackPointData>
     */
    #[DataCollectionOf(FeedbackPointData::class)]
    public array $improvements;

    /**
     * Concrete next actions.
     *
     * @var array<int, FeedbackPointData>
     */
    #[DataCollectionOf(FeedbackPointData::class)]
    public array $next_steps;

    /**
     * @param  int  $score  Internal 0-100 score; mentees only see AiScoreLabel.
     * @param  array<int, mixed>  $strengths  FeedbackPointData, {point, explanation} arrays or legacy strings.
     * @param  array<int, mixed>  $improvements  Same accepted shapes as $strengths.
     * @param  string  $feedback  Short overall summary.
     * @param  array<int, mixed>  $next_steps  Same accepted shapes as $strengths.
     */
    public function __construct(
        public int $score,
        array $strengths,
        array $improvements,
        public string $feedback,
        array $next_steps,
    ) {
        $this->strengths = FeedbackPointData::listFrom($strengths);
        $this->improvements = FeedbackPointData::listFrom($improvements);
        $this->next_steps = FeedbackPointData::listFrom($next_steps);
    }

    /**
     * Get the score clamped into the 0-100 range.
     */
    public function normalizedScore(): int
    {
        return max(0, min(100, $this->score));
    }
}
