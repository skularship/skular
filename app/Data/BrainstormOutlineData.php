<?php

namespace App\Data;

use Spatie\LaravelData\Data;

/**
 * The chapter-by-chapter writing outline returned by the AI.
 *
 * It never contains a finished practice answer: every point is grounded in
 * the mentee's brainstorming answers, and missing facts stay explicitly marked.
 */
class BrainstormOutlineData extends Data
{
    /**
     * @param  array<int, string>  $points
     * @param  array<int, string>  $writing_flow
     * @param  array<int, string>  $missing_information
     * @param  array<int, string>  $reflection_questions
     * @param  array<int, BrainstormOutlineSectionData>  $sections
     */
    public function __construct(
        public string $title,
        public string $core_idea,
        public array $points = [],
        public array $writing_flow = [],
        public array $missing_information = [],
        public array $reflection_questions = [],
        public array $sections = [],
    ) {}
}
