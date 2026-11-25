<?php

namespace App\Contracts;

use App\Enums\TryoutExamType;
use App\Enums\TryoutSkill;
use App\Models\TryoutAttempt;
use App\Models\TryoutAttemptSection;

/**
 * Stores the scores of a Try Out attempt using the strategy of its exam type.
 */
interface TryoutScoringServiceInterface
{
    /**
     * Score a closed Listening/Reading subtest (raw score, band, final level).
     */
    public function scoreObjective(TryoutAttemptSection $attempt_section): void;

    /**
     * Set the Writing/Speaking band once every AI assessment is complete.
     */
    public function scoreProductive(TryoutAttemptSection $attempt_section): void;

    /**
     * Band of an objective subtest from its raw score.
     */
    public function bandFromRaw(TryoutSkill $skill, int $raw, int $max = 40, TryoutExamType $exam_type = TryoutExamType::Ielts): float;

    /**
     * Writing band from the two task bands.
     */
    public function writingBand(float $task_one, float $task_two, TryoutExamType $exam_type = TryoutExamType::Ielts): float;

    /**
     * Round a band the way the exam reports it.
     */
    public function roundBand(float $value, TryoutExamType $exam_type = TryoutExamType::Ielts): float;

    /**
     * Compute and store the overall band; null while a subtest has no band yet.
     */
    public function overall(TryoutAttempt $attempt): ?float;

    /**
     * The strategy that scores an exam type.
     */
    public function strategyFor(TryoutExamType $exam_type): TryoutScoringStrategyInterface;
}
