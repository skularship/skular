<?php

namespace App\Data;

use Spatie\LaravelData\Data;

/**
 * Outcome of sending one Listening/Reading answer. Deliberately carries no
 * correct/incorrect flag so the mentee cannot tell during the test.
 */
class TryoutAnswerResultData extends Data
{
    /**
     * @param  TryoutQuestionViewData|null  $next_question  The next question, or null when the subtest ended.
     * @param  bool  $section_finished  Whether the subtest is closed now.
     * @param  int  $answered_count  Questions answered so far.
     * @param  bool  $new_block  Whether the next question opens a new unit (transition card).
     */
    public function __construct(
        public ?TryoutQuestionViewData $next_question,
        public bool $section_finished,
        public int $answered_count,
        public bool $new_block = false,
    ) {}
}
