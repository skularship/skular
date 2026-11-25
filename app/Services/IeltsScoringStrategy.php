<?php

namespace App\Services;

use App\Contracts\TryoutScoringStrategyInterface;
use App\Enums\TryoutExamType;
use App\Enums\TryoutSkill;

/**
 * IELTS band formulas (PRD 3.8): band tables for Listening/Reading from
 * config/tryout.php, Writing = (Task 1 + 2 x Task 2) / 3, criteria and
 * overall averaged, every band rounded to 0.5 (.25 up to .5, .75 up to 1).
 */
class IeltsScoringStrategy implements TryoutScoringStrategyInterface
{
    /**
     * Raw scores in the band tables are out of this many questions.
     */
    public const TABLE_QUESTION_COUNT = 40;

    /**
     * Whether this strategy scores the exam type.
     */
    public function supports(TryoutExamType $exam_type): bool
    {
        return $exam_type === TryoutExamType::Ielts;
    }

    /**
     * Band from the configured table; other question counts are scaled to 40 first.
     */
    public function bandFromRaw(TryoutSkill $skill, int $raw, int $max): float
    {
        $table = config('tryout.band_tables.'.$skill->value);

        if (! is_array($table) || $table === [] || $max <= 0) {
            return 0.0;
        }

        $scaled = $max === self::TABLE_QUESTION_COUNT
            ? $raw
            : (int) round($raw / $max * self::TABLE_QUESTION_COUNT);

        krsort($table, SORT_NUMERIC);

        foreach ($table as $raw_min => $band) {
            if ($scaled >= (int) $raw_min) {
                return (float) $band;
            }
        }

        return 0.0;
    }

    /**
     * Writing band: Task 2 counts twice.
     */
    public function writingBand(float $task_one, float $task_two): float
    {
        return $this->roundScore(($task_one + 2 * $task_two) / 3);
    }

    /**
     * Average of the criteria, rounded.
     *
     * @param  array<int, float>  $criteria
     */
    public function criteriaBand(array $criteria): float
    {
        if ($criteria === []) {
            return 0.0;
        }

        return $this->roundScore(array_sum($criteria) / count($criteria));
    }

    /**
     * Average of the four subtests, rounded.
     *
     * @param  array<int, float>  $section_scores
     */
    public function overall(array $section_scores): float
    {
        return $this->criteriaBand($section_scores);
    }

    /**
     * Round to the nearest half band; quarters round up.
     */
    public function roundScore(float $value): float
    {
        // Absorb float noise only (6.2499999 -> 6.25). Snapping to the nearest
        // quarter (as first drafted in the PRD) would turn 6.17 into 6.5.
        $clean = round($value, 4);

        return max(0.0, min(9.0, floor($clean * 2 + 0.5) / 2));
    }
}
