<?php

namespace App\Contracts;

use App\Enums\TryoutExamType;
use App\Enums\TryoutSkill;

/**
 * Score formulas of one exam type (IELTS in v1, SAT later). Adding an exam
 * means adding a strategy, never editing the IELTS one (PRD 2.3).
 */
interface TryoutScoringStrategyInterface
{
    /**
     * Whether this strategy scores the exam type.
     */
    public function supports(TryoutExamType $exam_type): bool;

    /**
     * Score of an objective subtest from its raw score.
     *
     * @param  int  $raw  Correct answers.
     * @param  int  $max  Question count of the subtest.
     */
    public function bandFromRaw(TryoutSkill $skill, int $raw, int $max): float;

    /**
     * Score of the Writing subtest from its two task bands.
     */
    public function writingBand(float $task_one, float $task_two): float;

    /**
     * Score of one AI-assessed item from its criteria bands.
     *
     * @param  array<int, float>  $criteria
     */
    public function criteriaBand(array $criteria): float;

    /**
     * Overall score from the subtest scores.
     *
     * @param  array<int, float>  $section_scores
     */
    public function overall(array $section_scores): float;

    /**
     * Round a score the way this exam reports it.
     */
    public function roundScore(float $value): float;
}
