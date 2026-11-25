<?php

namespace App\Contracts;

use App\Models\TryoutQuestion;

/**
 * Checks Listening/Reading answers against the key on the server.
 */
interface AnswerCheckerInterface
{
    /**
     * Whether the response matches the key or an accepted alternative.
     *
     * @param  TryoutQuestion  $question  The question with its hidden key.
     * @param  string  $response  The mentee answer.
     * @return bool True when correct.
     */
    public function isCorrect(TryoutQuestion $question, string $response): bool;

    /**
     * Normalise a short text answer for comparison.
     *
     * @return string Trimmed, lower case, single spaced, without a trailing period or thousands separators.
     */
    public function normalize(string $value): string;

    /**
     * Count the words of an answer.
     */
    public function countWords(string $value): int;
}
