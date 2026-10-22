<?php

namespace App\Data;

use Spatie\LaravelData\Data;

/**
 * The structured result of a brainstorming interaction.
 */
class BrainstormResultData extends Data
{
    /**
     * @param  array<int, BrainstormQuestionData>  $questions
     */
    public function __construct(
        public array $questions = [],
        public ?string $summary = null,
        public ?BrainstormOutlineData $outline = null,
    ) {}
}
