<?php

namespace App\Services;

use App\Contracts\IqScoringServiceInterface;
use App\Enums\IqSubtest;
use App\Models\IqAttempt;
use App\Models\IqQuestion;

/**
 * Scores a closed Tes IQ attempt on the server (model B): every answer is
 * marked against the key at closing time, then correct counts per subtest
 * and in total are stored with the rounded percentage and the category
 * from config/iq.php. No IQ number exists anywhere.
 */
class IqScoringService implements IqScoringServiceInterface
{
    /**
     * Mark every answer against the current key and store the scores on the attempt.
     */
    public function score(IqAttempt $attempt): void
    {
        $ids = array_map('intval', $attempt->question_ids);
        $questions = IqQuestion::query()->whereIn('id', $ids)->get()->keyBy('id');
        $answers = $attempt->answers()->get()->keyBy('iq_question_id');

        $per_subtest = [];
        $correct = 0;
        $answered = 0;

        foreach ($ids as $id) {
            $question = $questions->get($id);

            if (! $question instanceof IqQuestion) {
                continue;
            }

            $answer = $answers->get($id);
            $choice = $answer?->choice;
            $is_correct = $choice !== null && $choice === $question->answer_key;

            if ($answer !== null) {
                $answer->forceFill(['is_correct' => $choice === null ? null : $is_correct])->save();
            }

            $key = $question->subtest->value;
            $per_subtest[$key] ??= ['correct' => 0, 'total' => 0];
            $per_subtest[$key]['total']++;

            if ($choice !== null) {
                $answered++;
            }

            if ($is_correct) {
                $per_subtest[$key]['correct']++;
                $correct++;
            }
        }

        $ordered = [];

        foreach (IqSubtest::cases() as $subtest) {
            if (isset($per_subtest[$subtest->value])) {
                $ordered[$subtest->value] = $per_subtest[$subtest->value];
            }
        }

        $total = count($ids);
        $percent = $total > 0 ? (int) round($correct * 100 / $total) : 0;

        $attempt->forceFill([
            'answered_count' => $answered,
            'correct_count' => $correct,
            'score_percent' => $percent,
            'category' => $this->category($percent),
            'subtest_scores' => $ordered,
        ])->save();
    }

    /**
     * Category label of a total percentage (highest threshold first).
     */
    public function category(int $percent): string
    {
        $categories = (array) config('iq.categories', []);
        krsort($categories, SORT_NUMERIC);

        foreach ($categories as $threshold => $label) {
            if ($percent >= (int) $threshold) {
                return (string) $label;
            }
        }

        return (string) (end($categories) ?: '');
    }
}
