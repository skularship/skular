<?php

namespace App\Data;

use App\Models\TryoutQuestion;
use Spatie\LaravelData\Data;

/**
 * The only shape of a Try Out question that reaches a mentee page. Built
 * field by field so the key, accepted answers, outline and explanation can
 * never leak into Livewire state or HTML.
 */
class TryoutQuestionViewData extends Data
{
    /**
     * @param  string  $code  Question code; sent back with the answer.
     * @param  string  $type  TryoutQuestionType value.
     * @param  string  $skill  TryoutSkill value.
     * @param  string  $prompt  Question, task, or part title.
     * @param  array<int, string>  $prompt_items  Speaking questions or cue card bullets.
     * @param  array{columns?: array<int, string>, rows?: array<int, array<int, string>>}|null  $prompt_table  Writing Task 1 table.
     * @param  string|null  $prompt_image_url  Writing Task 1 chart.
     * @param  array<string, string>  $options  Letter => text.
     * @param  string|null  $source_type  Label such as "TRUE / FALSE / NOT GIVEN".
     * @param  int|null  $word_limit  Short text limit.
     * @param  int|null  $min_words  Writing minimum.
     * @param  int|null  $prep_seconds  Speaking preparation.
     * @param  int|null  $speak_seconds  Speaking maximum.
     * @param  int|null  $part  Writing task / Speaking part.
     * @param  int  $sequence  1-based serving number.
     * @param  int  $total  Question count of the subtest.
     * @param  TryoutUnitViewData|null  $unit  Passage/audio of the block.
     */
    public function __construct(
        public string $code,
        public string $type,
        public string $skill,
        public string $prompt,
        public array $prompt_items,
        public ?array $prompt_table,
        public ?string $prompt_image_url,
        public array $options,
        public ?string $source_type,
        public ?int $word_limit,
        public ?int $min_words,
        public ?int $prep_seconds,
        public ?int $speak_seconds,
        public ?int $part,
        public int $sequence,
        public int $total,
        public ?TryoutUnitViewData $unit = null,
    ) {}

    /**
     * Build the view of a question without any answer data.
     */
    public static function fromQuestion(TryoutQuestion $question, int $sequence, int $total, ?TryoutUnitViewData $unit = null): self
    {
        return new self(
            code: $question->code,
            type: $question->question_type->value,
            skill: $question->skill->value,
            prompt: $question->prompt,
            prompt_items: array_values(array_filter($question->prompt_items ?? [], 'is_string')),
            prompt_table: $question->prompt_table,
            prompt_image_url: $question->promptImageUrl(),
            options: $question->optionList(),
            source_type: $question->source_type,
            word_limit: $question->word_limit,
            min_words: $question->min_words,
            prep_seconds: $question->prep_seconds,
            speak_seconds: $question->speak_seconds,
            part: $question->part,
            sequence: $sequence,
            total: $total,
            unit: $unit,
        );
    }
}
