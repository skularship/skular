<?php

namespace App\Services;

use App\Contracts\ModuleFeedbackInsightsServiceInterface;
use App\Data\ModuleFeedbackSummaryData;
use App\Enums\FeedbackDifficulty;
use App\Models\Module;
use App\Models\ModuleFeedback;
use Illuminate\Database\Eloquent\Builder;

/**
 * Aggregates the mandatory end-of-module feedback for the admin dashboard
 * widget "Ringkasan Feedback Modul". Read-only; feedback from the old
 * checklist form (no helpful_score) is left out because it has no score or
 * difficulty to count.
 */
class ModuleFeedbackInsightsService implements ModuleFeedbackInsightsServiceInterface
{
    /**
     * Number of modules listed in the "needs attention" ranking.
     */
    public const MODULE_LIMIT = 6;

    /**
     * Number of recent low-score comments shown.
     */
    public const RECENT_LIMIT = 5;

    /**
     * Summarise the feedback sent with the mandatory form (rows with a helpfulness score).
     *
     * @param  int  $days  Only feedback sent in the last N days; 0 means all time.
     * @param  int|null  $module_id  Limit to one module; null means every module.
     * @return ModuleFeedbackSummaryData The totals, distributions, module ranking and recent low scores.
     */
    public function summary(int $days, ?int $module_id = null): ModuleFeedbackSummaryData
    {
        $low = ModuleFeedbackService::LOW_SCORE_MAX;

        /** @var object{total: int|string|null, mentees: int|string|null, average_score: float|string|null, low_score_count: int|string|null}|null $totals */
        $totals = $this->scoped($days, $module_id)
            ->toBase()
            ->selectRaw('count(*) as total, count(distinct user_id) as mentees, avg(helpful_score) as average_score')
            ->selectRaw('sum(case when helpful_score <= ? then 1 else 0 end) as low_score_count', [$low])
            ->first();

        $total = (int) ($totals->total ?? 0);

        return new ModuleFeedbackSummaryData(
            total: $total,
            mentees: (int) ($totals->mentees ?? 0),
            average_score: $total > 0 && $totals?->average_score !== null ? round((float) $totals->average_score, 1) : null,
            low_score_count: (int) ($totals->low_score_count ?? 0),
            score_distribution: $this->scoreDistribution($days, $module_id),
            difficulties: $this->difficulties($days, $module_id, $total),
            modules: $this->modules($days, $module_id),
            recent_low_scores: $this->recentLowScores($days, $module_id),
        );
    }

    /**
     * The modules that already received feedback, for the widget's module filter.
     *
     * @return array<int, string> Module titles keyed by module id.
     */
    public function modulesWithFeedback(): array
    {
        /** @var array<int, string> $titles */
        $titles = Module::query()
            ->whereIn('id', ModuleFeedback::query()->whereNotNull('helpful_score')->select('module_id'))
            ->orderBy('title')
            ->pluck('title', 'id')
            ->all();

        return $titles;
    }

    /**
     * Feedback with a helpfulness score, limited to the window and module.
     *
     * @return Builder<ModuleFeedback>
     */
    private function scoped(int $days, ?int $module_id): Builder
    {
        return ModuleFeedback::query()
            ->whereNotNull('helpful_score')
            ->when($days > 0, fn (Builder $query) => $query->where('updated_at', '>=', now()->subDays($days)))
            ->when($module_id !== null, fn (Builder $query) => $query->where('module_id', $module_id));
    }

    /**
     * Count per helpfulness score 1-5 (missing scores are 0).
     *
     * @return array<int, int>
     */
    private function scoreDistribution(int $days, ?int $module_id): array
    {
        /** @var array<int|string, int|string> $counts */
        $counts = $this->scoped($days, $module_id)
            ->toBase()
            ->selectRaw('helpful_score, count(*) as total')
            ->groupBy('helpful_score')
            ->pluck('total', 'helpful_score')
            ->all();

        $distribution = [];

        foreach (range(1, 5) as $score) {
            $distribution[$score] = (int) ($counts[$score] ?? 0);
        }

        return $distribution;
    }

    /**
     * Every difficulty option with its count and share, hardest parts first and "Tidak ada" last.
     *
     * @return array<int, array{difficulty: FeedbackDifficulty, count: int, percentage: int}>
     */
    private function difficulties(int $days, ?int $module_id, int $total): array
    {
        /** @var array<string, int|string> $counts */
        $counts = $this->scoped($days, $module_id)
            ->whereNotNull('difficulty')
            ->toBase()
            ->selectRaw('difficulty, count(*) as total')
            ->groupBy('difficulty')
            ->pluck('total', 'difficulty')
            ->all();

        $rows = array_map(function (FeedbackDifficulty $difficulty) use ($counts, $total): array {
            $count = (int) ($counts[$difficulty->value] ?? 0);

            return [
                'difficulty' => $difficulty,
                'count' => $count,
                'percentage' => $total === 0 ? 0 : (int) round($count / $total * 100),
            ];
        }, FeedbackDifficulty::cases());

        usort($rows, function (array $a, array $b): int {
            $a_is_none = $a['difficulty'] === FeedbackDifficulty::TidakAda;
            $b_is_none = $b['difficulty'] === FeedbackDifficulty::TidakAda;

            if ($a_is_none !== $b_is_none) {
                return $a_is_none ? 1 : -1;
            }

            return $b['count'] <=> $a['count'];
        });

        return $rows;
    }

    /**
     * Modules ranked by lowest average helpfulness, then by most feedback.
     *
     * @return array<int, array{module_id: int, title: string, total: int, average_score: float, low_score_count: int, top_difficulty: FeedbackDifficulty|null}>
     */
    private function modules(int $days, ?int $module_id): array
    {
        $rows = $this->scoped($days, $module_id)
            ->toBase()
            ->selectRaw('module_id, count(*) as total, avg(helpful_score) as average_score')
            ->selectRaw('sum(case when helpful_score <= ? then 1 else 0 end) as low_score_count', [ModuleFeedbackService::LOW_SCORE_MAX])
            ->groupBy('module_id')
            ->orderBy('average_score')
            ->orderByDesc('total')
            ->limit(self::MODULE_LIMIT)
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $module_ids = $rows->pluck('module_id')->map(fn (mixed $id): int => (int) $id)->all();

        /** @var array<int, string> $titles */
        $titles = Module::query()->whereIn('id', $module_ids)->pluck('title', 'id')->all();

        $top_difficulties = $this->topDifficultyPerModule($days, $module_ids);

        return $rows->map(function (object $row) use ($titles, $top_difficulties): array {
            /** @var object{module_id: int|string, total: int|string, average_score: float|string, low_score_count: int|string|null} $row */
            $id = (int) $row->module_id;

            return [
                'module_id' => $id,
                'title' => $titles[$id] ?? 'Modul #'.$id,
                'total' => (int) $row->total,
                'average_score' => round((float) $row->average_score, 1),
                'low_score_count' => (int) $row->low_score_count,
                'top_difficulty' => $top_difficulties[$id] ?? null,
            ];
        })->values()->all();
    }

    /**
     * The most chosen difficulty per module, ignoring "Tidak ada".
     *
     * @param  array<int, int>  $module_ids
     * @return array<int, FeedbackDifficulty>
     */
    private function topDifficultyPerModule(int $days, array $module_ids): array
    {
        $rows = $this->scoped($days, null)
            ->whereIn('module_id', $module_ids)
            ->whereNotNull('difficulty')
            ->where('difficulty', '!=', FeedbackDifficulty::TidakAda->value)
            ->toBase()
            ->selectRaw('module_id, difficulty, count(*) as total')
            ->groupBy('module_id', 'difficulty')
            ->orderByDesc('total')
            ->get();

        $top = [];

        foreach ($rows as $row) {
            /** @var object{module_id: int|string, difficulty: string, total: int|string} $row */
            $id = (int) $row->module_id;
            $difficulty = FeedbackDifficulty::tryFrom($row->difficulty);

            if (! isset($top[$id]) && $difficulty !== null) {
                $top[$id] = $difficulty;
            }
        }

        return $top;
    }

    /**
     * The latest feedback scored 1-3, newest first.
     *
     * @return array<int, array{mentee: string, module: string, score: int, difficulty: FeedbackDifficulty|null, reason: string|null, open_answer: string|null, sent_at: string}>
     */
    private function recentLowScores(int $days, ?int $module_id): array
    {
        return $this->scoped($days, $module_id)
            ->where('helpful_score', '<=', ModuleFeedbackService::LOW_SCORE_MAX)
            ->with(['user:id,name', 'module:id,title'])
            ->latest('updated_at')
            ->limit(self::RECENT_LIMIT)
            ->get()
            ->map(fn (ModuleFeedback $feedback): array => [
                'mentee' => $feedback->user->name ?? 'Mentee',
                'module' => $feedback->module->title ?? 'Modul #'.$feedback->module_id,
                'score' => (int) $feedback->helpful_score,
                'difficulty' => $feedback->difficulty,
                'reason' => $feedback->low_score_reason,
                'open_answer' => $feedback->open_answer,
                'sent_at' => $feedback->updated_at?->diffForHumans() ?? '-',
            ])
            ->all();
    }
}
