<?php

namespace App\Data;

use Spatie\LaravelData\Data;

/**
 * One answered Listening/Reading question with its key and explanation.
 * Only built for Pro mentees (TryoutResultService::review()).
 */
class TryoutReviewItemData extends Data
{
    /**
     * @param  string  $skill  TryoutSkill value.
     * @param  int  $sequence  Serving number.
     * @param  string  $code  Question code (used by the mentor).
     * @param  string  $prompt  Question text.
     * @param  array<string, string>  $options  Letter => text.
     * @param  string|null  $response  Mentee answer.
     * @param  bool  $is_correct  Whether the answer matched.
     * @param  string|null  $answer_key  Correct answer.
     * @param  array<int, string>  $accepted_answers  Alternatives for short text.
     * @param  string|null  $explanation  Indonesian explanation.
     * @param  int  $level_at_answer  Adaptive level when served.
     * @param  string|null  $unit_title  Passage/audio title.
     * @param  string|null  $source_type  Question type label.
     */
    public function __construct(
        public string $skill,
        public int $sequence,
        public string $code,
        public string $prompt,
        public array $options,
        public ?string $response,
        public bool $is_correct,
        public ?string $answer_key,
        public array $accepted_answers,
        public ?string $explanation,
        public int $level_at_answer,
        public ?string $unit_title,
        public ?string $source_type,
    ) {}
}
