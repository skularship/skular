<?php

namespace App\Services;

use App\Contracts\AnswerCheckerInterface;
use App\Models\TryoutQuestion;

/**
 * Compares a Listening/Reading answer with the key and accepted answers.
 * Option questions compare the letter; short text compares normalised text
 * (case, spaces, trailing period and "2,600" = "2600" are ignored).
 */
class AnswerChecker implements AnswerCheckerInterface
{
    /**
     * Whether the response matches the key or an accepted alternative.
     *
     * With tryout.strict_word_limit on, a short text answer over the word
     * limit is wrong even when it contains the key (as in the real test, D12).
     */
    public function isCorrect(TryoutQuestion $question, string $response): bool
    {
        $response = trim($response);

        if ($response === '' || blank($question->answer_key)) {
            return false;
        }

        if ($question->question_type->hasOptions()) {
            return strtoupper($response) === strtoupper(trim((string) $question->answer_key));
        }

        if ((bool) config('tryout.strict_word_limit', false)
            && $question->word_limit !== null
            && $this->countWords($response) > $question->word_limit) {
            return false;
        }

        $given = $this->normalize($response);
        $accepted = array_merge([(string) $question->answer_key], $question->accepted_answers ?? []);

        foreach ($accepted as $candidate) {
            if ($candidate !== '' && $this->normalize($candidate) === $given) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normalise a short text answer for comparison.
     */
    public function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value));
        // Thousands separators: 2,600 / 2.600 -> 2600 (only between digit groups of three).
        $value = (string) preg_replace('/(?<=\d)[,.](?=\d{3}(\D|$))/u', '', $value);
        $value = (string) preg_replace('/\s+/u', ' ', $value);

        return rtrim($value, " .\t\n\r\0\x0B");
    }

    /**
     * Count the words of an answer.
     */
    public function countWords(string $value): int
    {
        $value = trim($value);

        if ($value === '') {
            return 0;
        }

        return count(preg_split('/\s+/u', $value) ?: []);
    }
}
