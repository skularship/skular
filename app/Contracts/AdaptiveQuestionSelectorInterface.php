<?php

namespace App\Contracts;

use App\Models\TryoutAttemptSection;
use App\Models\TryoutQuestion;
use App\Models\TryoutUnit;

/**
 * Picks the next Listening/Reading question with the unit block algorithm
 * (PRD 3.7): one unit per block, the question level follows the answers.
 */
interface AdaptiveQuestionSelectorInterface
{
    /**
     * Pick the next question to serve, or null when the pool is exhausted.
     *
     * Does not write anything; the caller stores the served row.
     */
    public function next(TryoutAttemptSection $attempt_section): ?TryoutQuestion;

    /**
     * Level after an answer: one up when correct, one down when wrong, within 1..5.
     */
    public function nextLevel(int $level, bool $correct): int;

    /**
     * Pick the unit of a new block for the given level, or null.
     */
    public function pickUnit(TryoutAttemptSection $attempt_section, int $level): ?TryoutUnit;
}
