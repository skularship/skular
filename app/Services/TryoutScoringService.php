<?php

namespace App\Services;

use App\Contracts\TryoutScoringServiceInterface;
use App\Contracts\TryoutScoringStrategyInterface;
use App\Data\TryoutAssessmentData;
use App\Enums\AiFeedbackStatus;
use App\Enums\TryoutExamType;
use App\Enums\TryoutSectionStatus;
use App\Enums\TryoutSkill;
use App\Models\TryoutAnswer;
use App\Models\TryoutAttempt;
use App\Models\TryoutAttemptSection;
use LogicException;

/**
 * Picks the scoring strategy from the exam type and stores raw scores,
 * bands and the overall band. Formulas live in the strategies.
 */
class TryoutScoringService implements TryoutScoringServiceInterface
{
    /**
     * @param  array<int, TryoutScoringStrategyInterface>  $strategies  Every registered strategy.
     */
    public function __construct(
        private readonly array $strategies = [new IeltsScoringStrategy],
    ) {}

    /**
     * Score a closed Listening/Reading subtest.
     *
     * Unanswered questions (no row, or no response) count as wrong.
     */
    public function scoreObjective(TryoutAttemptSection $attempt_section): void
    {
        $section = $attempt_section->section;
        $exam_type = $section->tryout->exam_type;
        $raw = $attempt_section->answers()->where('is_correct', true)->count();
        $max = $section->question_count;

        $attempt_section->forceFill([
            'raw_score' => $raw,
            'max_score' => $max,
            'band' => $this->bandFromRaw($attempt_section->skill, $raw, $max, $exam_type),
            'final_level' => $attempt_section->current_level ?? $section->start_level,
            'status' => TryoutSectionStatus::Scored,
        ])->save();
    }

    /**
     * Set the Writing/Speaking band once every AI assessment is complete.
     *
     * Writing: (Task 1 + 2 x Task 2) / 3 from the task bands. Speaking: the
     * band of the section assessment. Leaves the band empty while anything
     * is pending or failed.
     */
    public function scoreProductive(TryoutAttemptSection $attempt_section): void
    {
        $strategy = $this->strategyFor($attempt_section->section->tryout->exam_type);

        if ($attempt_section->skill === TryoutSkill::Writing) {
            $answers = $attempt_section->answers()->with('question:id,part')->get();

            if ($answers->isEmpty() || $answers->contains(fn (TryoutAnswer $answer): bool => $answer->ai_status !== AiFeedbackStatus::Completed || $answer->band === null)) {
                return;
            }

            $task_one = (float) ($answers->first(fn (TryoutAnswer $answer): bool => $answer->question->part === 1)->band ?? 0.0);
            $task_two = (float) ($answers->first(fn (TryoutAnswer $answer): bool => $answer->question->part === 2)->band ?? $task_one);

            $attempt_section->forceFill([
                'band' => $strategy->writingBand($task_one, $task_two),
                'status' => TryoutSectionStatus::Scored,
            ])->save();

            return;
        }

        if ($attempt_section->skill === TryoutSkill::Speaking) {
            $assessment = TryoutAssessmentData::fromStored($attempt_section->ai_assessment);

            if ($attempt_section->ai_status !== AiFeedbackStatus::Completed || $assessment === null) {
                return;
            }

            $attempt_section->forceFill([
                'band' => $strategy->roundScore($assessment->band),
                'status' => TryoutSectionStatus::Scored,
            ])->save();
        }
    }

    /**
     * Band of an objective subtest from its raw score.
     */
    public function bandFromRaw(TryoutSkill $skill, int $raw, int $max = 40, TryoutExamType $exam_type = TryoutExamType::Ielts): float
    {
        return $this->strategyFor($exam_type)->bandFromRaw($skill, $raw, $max);
    }

    /**
     * Writing band from the two task bands.
     */
    public function writingBand(float $task_one, float $task_two, TryoutExamType $exam_type = TryoutExamType::Ielts): float
    {
        return $this->strategyFor($exam_type)->writingBand($task_one, $task_two);
    }

    /**
     * Round a band the way the exam reports it.
     */
    public function roundBand(float $value, TryoutExamType $exam_type = TryoutExamType::Ielts): float
    {
        return $this->strategyFor($exam_type)->roundScore($value);
    }

    /**
     * Compute and store the overall band; null while a subtest has no band yet.
     */
    public function overall(TryoutAttempt $attempt): ?float
    {
        $sections = $attempt->sections()->get();

        if ($sections->isEmpty() || $sections->contains(fn (TryoutAttemptSection $section): bool => $section->band === null)) {
            return null;
        }

        $overall = $this->strategyFor($attempt->tryout->exam_type)
            ->overall($sections->map(fn (TryoutAttemptSection $section): float => (float) $section->band)->all());

        $attempt->forceFill(['overall_band' => $overall])->save();

        return $overall;
    }

    /**
     * The strategy that scores an exam type.
     *
     * @throws LogicException When no strategy supports the exam type.
     */
    public function strategyFor(TryoutExamType $exam_type): TryoutScoringStrategyInterface
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->supports($exam_type)) {
                return $strategy;
            }
        }

        throw new LogicException('Belum ada rumus skor untuk jenis ujian '.$exam_type->value.'.');
    }
}
