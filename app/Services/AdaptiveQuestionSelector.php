<?php

namespace App\Services;

use App\Contracts\AdaptiveQuestionSelectorInterface;
use App\Enums\ContentStatus;
use App\Models\TryoutAnswer;
use App\Models\TryoutAttemptSection;
use App\Models\TryoutQuestion;
use App\Models\TryoutSection;
use App\Models\TryoutUnit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Unit block algorithm of the adaptive Listening/Reading subtests (PRD 3.7).
 *
 * A block is one unit (one audio or one passage) serving `perUnit()`
 * questions. A new block takes the unused unit whose average question level
 * is closest to the current level (ties: pivot order). Inside the block the
 * question on the current level comes first, then the nearest level going
 * up (level+1..5), then down (level-1..1); questions this mentee never saw
 * in another attempt are preferred. Pure reads only: the answer service
 * stores the served row.
 */
class AdaptiveQuestionSelector implements AdaptiveQuestionSelectorInterface
{
    /**
     * Pick the next question to serve, or null when the pool is exhausted.
     */
    public function next(TryoutAttemptSection $attempt_section): ?TryoutQuestion
    {
        $section = $attempt_section->section;
        $level = $attempt_section->current_level ?? $section->start_level;
        $served = $this->servedRows($attempt_section);
        $served_ids = $served->pluck('tryout_question_id')->all();

        $unit = $attempt_section->currentUnit;

        if ($unit !== null && $this->blockIsOpen($served, $unit, $section)) {
            $question = $this->questionInUnit($attempt_section, $unit, $level, $served_ids);

            if ($question !== null) {
                return $question;
            }
        }

        $next_unit = $this->pickUnit($attempt_section, $level);

        if ($next_unit === null) {
            return null;
        }

        return $this->questionInUnit($attempt_section, $next_unit, $level, $served_ids);
    }

    /**
     * Level after an answer, kept within the configured bounds.
     */
    public function nextLevel(int $level, bool $correct): int
    {
        $min = (int) config('tryout.adaptive.min_level', 1);
        $max = (int) config('tryout.adaptive.max_level', 5);

        return $correct ? min($max, $level + 1) : max($min, $level - 1);
    }

    /**
     * Pick the unit of a new block.
     *
     * Prefers units not used yet in this subtest; falls back to a used unit
     * that still has unserved questions when every unit was used.
     */
    public function pickUnit(TryoutAttemptSection $attempt_section, int $level): ?TryoutUnit
    {
        $served = $this->servedRows($attempt_section);
        $served_ids = $served->pluck('tryout_question_id')->all();
        $used_unit_ids = $served->map(fn (TryoutAnswer $row): ?int => $row->question->tryout_unit_id)->filter()->unique()->all();

        $candidates = $attempt_section->section->units()
            ->where('tryout_units.status', ContentStatus::Published->value)
            ->get()
            ->filter(fn (TryoutUnit $unit): bool => $this->remainingQuery($unit, $served_ids)->exists())
            ->values();

        if ($candidates->isEmpty()) {
            return null;
        }

        $fresh = $candidates->reject(fn (TryoutUnit $unit): bool => in_array($unit->id, $used_unit_ids, true))->values();
        $pool = $fresh->isNotEmpty() ? $fresh : $candidates;

        // Stable sort: distance to the current level, then the pivot order.
        return $pool
            ->map(fn (TryoutUnit $unit, int $index): array => [
                'unit' => $unit,
                'distance' => abs($unit->averageLevel() - $level),
                'index' => $index,
            ])
            ->sort(fn (array $a, array $b): int => [$a['distance'], $a['index']] <=> [$b['distance'], $b['index']])
            ->first()['unit'] ?? null;
    }

    /**
     * Whether the active block may serve another question.
     *
     * Counts the trailing served rows of this unit, so a unit reused as a
     * fallback starts a fresh block.
     *
     * @param  Collection<int, TryoutAnswer>  $served
     */
    private function blockIsOpen(Collection $served, TryoutUnit $unit, TryoutSection $section): bool
    {
        $in_block = 0;

        foreach ($served->sortByDesc('sequence') as $row) {
            if ($row->question->tryout_unit_id !== $unit->id) {
                break;
            }

            $in_block++;
        }

        return $in_block < $section->perUnit();
    }

    /**
     * Nearest-level unserved question of a unit: level, then up, then down.
     *
     * @param  array<int, int>  $served_ids
     */
    private function questionInUnit(TryoutAttemptSection $attempt_section, TryoutUnit $unit, int $level, array $served_ids): ?TryoutQuestion
    {
        $remaining = $this->remainingQuery($unit, $served_ids)->get();

        if ($remaining->isEmpty()) {
            return null;
        }

        $max = (int) config('tryout.adaptive.max_level', 5);
        $min = (int) config('tryout.adaptive.min_level', 1);
        $order = array_merge([$level], range(min($max, $level + 1), $max), $level - 1 >= $min ? range($level - 1, $min) : []);
        $order = array_values(array_unique(array_filter($order, fn (int $value): bool => $value >= $min && $value <= $max)));

        $seen_before = $this->questionsSeenInOtherAttempts($attempt_section, $remaining->pluck('id')->all());

        foreach ($order as $candidate_level) {
            $on_level = $remaining->where('level', $candidate_level);

            if ($on_level->isEmpty()) {
                continue;
            }

            return $on_level->first(fn (TryoutQuestion $question): bool => ! in_array($question->id, $seen_before, true))
                ?? $on_level->first();
        }

        return $remaining->first();
    }

    /**
     * Published, unserved questions of a unit in a stable order.
     *
     * @param  array<int, int>  $served_ids
     * @return Builder<TryoutQuestion>
     */
    private function remainingQuery(TryoutUnit $unit, array $served_ids): Builder
    {
        return TryoutQuestion::query()
            ->where('tryout_unit_id', $unit->id)
            ->where('status', ContentStatus::Published->value)
            ->whereNotIn('id', $served_ids)
            ->orderBy('level')
            ->orderBy('number')
            ->orderBy('id');
    }

    /**
     * Ids among the candidates that this mentee answered in an earlier attempt.
     *
     * @param  array<int, int>  $candidate_ids
     * @return array<int, int>
     */
    private function questionsSeenInOtherAttempts(TryoutAttemptSection $attempt_section, array $candidate_ids): array
    {
        $user_id = $attempt_section->attempt->user_id;

        return TryoutAnswer::query()
            ->whereIn('tryout_question_id', $candidate_ids)
            ->where('tryout_attempt_section_id', '!=', $attempt_section->id)
            ->whereHas('attemptSection.attempt', fn ($query) => $query->where('user_id', $user_id))
            ->pluck('tryout_question_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }

    /**
     * Rows already served in this subtest, with their question.
     *
     * @return Collection<int, TryoutAnswer>
     */
    private function servedRows(TryoutAttemptSection $attempt_section): Collection
    {
        return $attempt_section->answers()->with('question:id,tryout_unit_id')->get();
    }
}
