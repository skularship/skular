<?php

namespace App\Services;

use App\Contracts\EntitlementServiceInterface;
use App\Contracts\TryoutResultServiceInterface;
use App\Data\CriterionBandData;
use App\Data\TryoutAssessmentData;
use App\Data\TryoutResultData;
use App\Data\TryoutReviewItemData;
use App\Data\TryoutSectionResultData;
use App\Enums\AiFeedbackStatus;
use App\Enums\TryoutSkill;
use App\Exceptions\TryoutReviewLockedException;
use App\Models\TryoutAnswer;
use App\Models\TryoutAttempt;
use App\Models\TryoutAttemptSection;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Result page data of a Try Out attempt (free for everyone) and the Pro
 * review. The key and explanation only leave this service through
 * review(), which refuses Gratis mentees, so they never reach a Gratis browser.
 */
class TryoutResultService implements TryoutResultServiceInterface
{
    /**
     * @param  EntitlementServiceInterface  $entitlements  Plan rules (tryout_review).
     */
    public function __construct(
        private readonly EntitlementServiceInterface $entitlements,
    ) {}

    /**
     * The free result: bands, raw scores, final levels and W/S criteria.
     */
    public function result(TryoutAttempt $attempt, User $viewer): TryoutResultData
    {
        $attempt->loadMissing(['tryout', 'sections.answers.question']);

        $sections = $attempt->sections
            ->map(fn (TryoutAttemptSection $section): TryoutSectionResultData => $this->sectionResult($section))
            ->values()
            ->all();

        $with_band = collect($sections)->filter(fn (TryoutSectionResultData $section): bool => $section->band !== null);
        $all_scored = $with_band->count() === count($sections) && $sections !== [];

        return new TryoutResultData(
            attempt_id: $attempt->id,
            tryout_title: $attempt->tryout->title,
            overall_band: $all_scored ? $attempt->overall_band : null,
            sections: $sections,
            is_estimate: true,
            can_review: $this->entitlements->can($viewer, 'tryout_review'),
            strongest: $with_band->count() >= 2 ? $with_band->sortByDesc('band')->first()?->label : null,
            weakest: $with_band->count() >= 2 ? $with_band->sortBy('band')->first()?->label : null,
            needs_assessment: collect($sections)->contains(fn (TryoutSectionResultData $section): bool => $section->needs_assessment),
        );
    }

    /**
     * Every answered Listening/Reading question with key and explanation (Pro only).
     *
     * @return Collection<int, TryoutReviewItemData>
     *
     * @throws TryoutReviewLockedException
     */
    public function review(User $user, TryoutAttempt $attempt): Collection
    {
        if (! $this->entitlements->can($user, 'tryout_review')) {
            throw new TryoutReviewLockedException;
        }

        return $attempt->answers()
            ->with(['question.unit', 'attemptSection'])
            ->whereNotNull('answered_at')
            ->whereHas('attemptSection', fn ($query) => $query->whereIn('skill', [TryoutSkill::Listening->value, TryoutSkill::Reading->value]))
            ->orderBy('tryout_attempt_section_id')
            ->orderBy('sequence')
            ->get()
            ->map(function (TryoutAnswer $answer): TryoutReviewItemData {
                $question = $answer->question;

                return new TryoutReviewItemData(
                    skill: $answer->attemptSection->skill->value,
                    sequence: $answer->sequence,
                    code: $question->code,
                    prompt: $question->prompt,
                    options: $question->optionList(),
                    response: $answer->response,
                    is_correct: (bool) $answer->is_correct,
                    answer_key: $question->answer_key,
                    accepted_answers: array_values(array_filter($question->accepted_answers ?? [], 'is_string')),
                    explanation: $question->explanation,
                    level_at_answer: $answer->level_at_answer,
                    unit_title: $question->unit?->title,
                    source_type: $question->source_type,
                );
            })
            ->values()
            ->toBase();
    }

    /**
     * Result card of one subtest.
     */
    private function sectionResult(TryoutAttemptSection $section): TryoutSectionResultData
    {
        $skill = $section->skill;

        if ($skill->isObjective()) {
            return new TryoutSectionResultData(
                skill: $skill->value,
                label: $skill->getLabel(),
                raw_score: $section->raw_score,
                max_score: $section->max_score,
                band: $section->band,
                final_level: $section->final_level,
                ai_status: null,
            );
        }

        $items = [];
        $criteria = [];
        $summary = '';

        foreach ($section->answers as $answer) {
            $part = $answer->question->part ?? $answer->sequence;

            $items[] = [
                'label' => $skill->itemLabel($part),
                'band' => $skill === TryoutSkill::Writing ? $answer->band : null,
                'status' => $answer->ai_status?->value,
                'answer_id' => $answer->id,
                'can_retry' => $answer->canRetryAi(),
            ];
        }

        if ($skill === TryoutSkill::Writing) {
            // Criteria of the Writing subtest = weighted like the band (Task 2 counts twice).
            $criteria = $this->writingCriteria($section);
            $summary = (string) (TryoutAssessmentData::fromStored($section->answers->sortByDesc(fn (TryoutAnswer $answer): int => $answer->question->part ?? 0)->first()?->ai_assessment)->summary ?? '');
            $statuses = $section->answers->map(fn (TryoutAnswer $answer): ?AiFeedbackStatus => $answer->ai_status);
            $ai_status = $this->combinedStatus($statuses->all());
            $can_retry = collect($items)->contains(fn (array $item): bool => $item['can_retry']);
        } else {
            $assessment = TryoutAssessmentData::fromStored($section->ai_assessment);
            $criteria = $assessment->criteria ?? [];
            $summary = $assessment->summary ?? '';
            $ai_status = $section->ai_status;
            $max = (int) config('tryout.ai.max_retries_per_answer', 2);
            $can_retry = ($section->ai_status === AiFeedbackStatus::Failed || $section->isStalled()) && $section->ai_attempts <= $max;
        }

        return new TryoutSectionResultData(
            skill: $skill->value,
            label: $skill->getLabel(),
            raw_score: null,
            max_score: null,
            band: $section->band,
            final_level: null,
            ai_status: $ai_status?->value,
            criteria: $criteria,
            items: $items,
            summary: $summary,
            needs_assessment: $section->band === null && in_array($ai_status, [AiFeedbackStatus::Pending, AiFeedbackStatus::Processing], true),
            can_retry: $can_retry,
        );
    }

    /**
     * Writing criteria averaged over the tasks with Task 2 counted twice.
     *
     * @return array<int, CriterionBandData>
     */
    private function writingCriteria(TryoutAttemptSection $section): array
    {
        $weighted = [];

        foreach ($section->answers as $answer) {
            $assessment = TryoutAssessmentData::fromStored($answer->ai_assessment);

            if ($assessment === null) {
                return [];
            }

            $weight = ($answer->question->part ?? 1) === 2 ? 2 : 1;

            foreach ($assessment->criteria as $criterion) {
                $weighted[$criterion->key]['label'] ??= $criterion->label;
                $weighted[$criterion->key]['sum'] = ($weighted[$criterion->key]['sum'] ?? 0) + $criterion->band * $weight;
                $weighted[$criterion->key]['weight'] = ($weighted[$criterion->key]['weight'] ?? 0) + $weight;
                // Task 2 reason wins: it is the task that counts most.
                if ($weight === 2 || ! isset($weighted[$criterion->key]['reason'])) {
                    $weighted[$criterion->key]['reason'] = $criterion->reason;
                }
                if ($criterion->key === 'task') {
                    $weighted[$criterion->key]['label'] = 'Task Achievement / Response';
                }
            }
        }

        $strategy = new IeltsScoringStrategy;
        $criteria = [];

        foreach ($weighted as $key => $row) {
            $criteria[] = new CriterionBandData(
                key: (string) $key,
                label: (string) ($row['label'] ?? $key),
                band: $strategy->roundScore((float) ($row['sum'] ?? 0) / max(1, (int) ($row['weight'] ?? 1))),
                reason: (string) ($row['reason'] ?? ''),
            );
        }

        return $criteria;
    }

    /**
     * One status for several AI steps: failed > processing > pending > completed.
     *
     * @param  array<int, AiFeedbackStatus|null>  $statuses
     */
    private function combinedStatus(array $statuses): ?AiFeedbackStatus
    {
        $statuses = array_values(array_filter($statuses));

        foreach ([AiFeedbackStatus::Failed, AiFeedbackStatus::Processing, AiFeedbackStatus::Pending] as $status) {
            if (in_array($status, $statuses, true)) {
                return $status;
            }
        }

        return $statuses === [] ? null : AiFeedbackStatus::Completed;
    }
}
