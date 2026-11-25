<?php

namespace App\Services;

use App\Contracts\TryoutReadinessServiceInterface;
use App\Enums\TryoutExamType;
use App\Enums\TryoutSkill;
use App\Models\Tryout;
use App\Models\TryoutSection;

/**
 * Publication rules of a Try Out (PRD 2.3 and 3.7): only enabled exam types,
 * IELTS needs exactly Listening, Reading, Writing, Speaking in that order,
 * every objective pool has at least question_count published questions,
 * every unit at least per_unit, and an adaptive pool at least
 * tryout.adaptive.min_pool_per_level per level. Writing/Speaking need one
 * prompt per task/part. A draft may be saved with problems.
 */
class TryoutReadinessService implements TryoutReadinessServiceInterface
{
    /**
     * Everything that still blocks publishing.
     *
     * @return array<int, string>
     */
    public function problems(Tryout $tryout): array
    {
        $problems = [];
        $sections = $tryout->sections()->with('units')->get();

        if (! $tryout->exam_type->isEnabled()) {
            $problems[] = 'Jenis ujian '.$tryout->exam_type->getLabel().' belum dibuka untuk dipublikasikan.';
        }

        $required = $tryout->exam_type->requiredSkills();

        if ($tryout->exam_type === TryoutExamType::Ielts && $sections->pluck('skill')->all() !== $required) {
            $problems[] = 'Try Out IELTS wajib punya tepat 4 subtes berurutan: Listening, Reading, Writing, Speaking.';
        }

        foreach ($sections as $section) {
            $problems = [...$problems, ...$this->sectionProblems($section)];
        }

        return $problems;
    }

    /**
     * Problems of one subtest pool.
     *
     * @return array<int, string>
     */
    private function sectionProblems(TryoutSection $section): array
    {
        $label = $section->skill->getLabel();
        $problems = [];
        $count = $section->poolQuestions()->count();

        if ($section->units->isEmpty()) {
            return [$label.': belum ada unit di pool.'];
        }

        if (! $section->skill->isObjective()) {
            $parts = $section->poolQuestions()->whereNotNull('part')->distinct()->count('part');

            if ($parts < $section->question_count) {
                $problems[] = $label.': butuh '.$section->question_count.' '.($section->skill === TryoutSkill::Writing ? 'task' : 'part').' terbit, baru ada '.$parts.'.';
            }

            return $problems;
        }

        if ($count < $section->question_count) {
            $problems[] = $label.': pool baru '.$count.' soal terbit, butuh minimal '.$section->question_count.'.';
        }

        $per_unit = $section->perUnit();

        foreach ($section->units as $unit) {
            $unit_count = $section->poolQuestions()->where('tryout_unit_id', $unit->id)->count();

            if ($unit_count < $per_unit) {
                $problems[] = $label.': unit '.$unit->code.' baru '.$unit_count.' soal terbit, butuh '.$per_unit.'.';
            }
        }

        if ($section->is_adaptive) {
            $min = (int) config('tryout.adaptive.min_pool_per_level', 8);

            foreach ($section->poolCountByLevel() as $level => $level_count) {
                if ($level_count < $min) {
                    $problems[] = $label.': level '.$level.' baru '.$level_count.' soal, butuh minimal '.$min.' (adaptif).';
                }
            }
        }

        return $problems;
    }
}
