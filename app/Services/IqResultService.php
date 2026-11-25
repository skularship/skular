<?php

namespace App\Services;

use App\Contracts\EntitlementServiceInterface;
use App\Contracts\IqResultServiceInterface;
use App\Data\IqResultData;
use App\Data\IqReviewItemData;
use App\Data\IqSubtestScoreData;
use App\Enums\IqSubtest;
use App\Exceptions\IqLockedException;
use App\Models\IqAnswer;
use App\Models\IqAttempt;
use App\Models\IqQuestion;
use App\Models\User;

/**
 * Builds what a mentee sees after a Tes IQ. Everyone gets the answered
 * count; scores, category, key and explanation are only read for mentees
 * with paid access (plans.*.iq_premium), so they never reach the page of
 * anyone else.
 */
class IqResultService implements IqResultServiceInterface
{
    /**
     * @param  EntitlementServiceInterface  $entitlements  Plan rules (iq_premium).
     */
    public function __construct(
        private readonly EntitlementServiceInterface $entitlements,
    ) {}

    /**
     * Whether the mentee has paid access to the Tes IQ.
     */
    public function canSeeResults(User $user): bool
    {
        return $this->entitlements->can($user, 'iq_premium');
    }

    /**
     * Result of a closed attempt; score fields stay null without paid access.
     */
    public function result(IqAttempt $attempt, User $user): IqResultData
    {
        $answered = $attempt->answered_count ?? $attempt->answers()->whereNotNull('choice')->count();

        if (! $this->canSeeResults($user)) {
            return new IqResultData(
                unlocked: false,
                status_label: $attempt->status->getLabel(),
                answered_count: $answered,
                total_questions: $attempt->total_questions,
            );
        }

        $subtests = [];

        foreach ($attempt->subtest_scores ?? [] as $value => $score) {
            $subtest = IqSubtest::tryFrom((string) $value);

            if ($subtest === null) {
                continue;
            }

            $total = $score['total'];
            $correct = $score['correct'];

            $subtests[] = new IqSubtestScoreData(
                subtest: $subtest->value,
                label: $subtest->getLabel(),
                correct: $correct,
                total: $total,
                percent: $total > 0 ? (int) round($correct * 100 / $total) : 0,
            );
        }

        return new IqResultData(
            unlocked: true,
            status_label: $attempt->status->getLabel(),
            answered_count: $answered,
            total_questions: $attempt->total_questions,
            correct_count: $attempt->correct_count,
            score_percent: $attempt->score_percent,
            category: $attempt->category,
            subtests: $subtests,
        );
    }

    /**
     * Every question of the attempt with the mentee's choice, key and explanation.
     *
     * @return array<int, IqReviewItemData>
     *
     * @throws IqLockedException
     */
    public function review(IqAttempt $attempt, User $user): array
    {
        if (! $this->canSeeResults($user)) {
            throw new IqLockedException;
        }

        $ids = array_map('intval', $attempt->question_ids);
        $questions = IqQuestion::query()->whereIn('id', $ids)->get()->keyBy('id');
        $answers = $attempt->answers()->get()->keyBy('iq_question_id');
        $items = [];

        foreach ($ids as $index => $id) {
            $question = $questions->get($id);

            if (! $question instanceof IqQuestion) {
                continue;
            }

            /** @var IqAnswer|null $answer */
            $answer = $answers->get($id);

            $items[] = new IqReviewItemData(
                position: $index + 1,
                subtest_label: $question->subtest->getLabel(),
                passage: filled($question->passage) ? (string) $question->passage : null,
                prompt: $question->prompt,
                image_url: $question->imageUrl(),
                image_alt: filled($question->image_alt) ? (string) $question->image_alt : null,
                options: $question->optionList(),
                choice: $answer?->choice,
                answer_key: $question->answer_key,
                is_correct: (bool) $answer?->is_correct,
                explanation: filled($question->explanation) ? (string) $question->explanation : null,
            );
        }

        return $items;
    }
}
