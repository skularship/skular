<?php

namespace App\Data;

use App\Enums\FeedbackDifficulty;
use Spatie\LaravelData\Data;

/**
 * Summary of module feedback for the admin dashboard widget "Ringkasan
 * Feedback Modul": totals, helpfulness and difficulty distributions, the
 * modules that most need attention, and the latest low-score comments.
 */
class ModuleFeedbackSummaryData extends Data
{
    /**
     * @param  int  $total  Feedback rows with a helpfulness score.
     * @param  int  $mentees  Distinct mentees who sent them.
     * @param  float|null  $average_score  Average helpfulness 1-5 (null when there is no feedback).
     * @param  int  $low_score_count  Rows scored 1-3.
     * @param  array<int, int>  $score_distribution  Count per helpfulness score, keyed 1 to 5.
     * @param  array<int, array{difficulty: FeedbackDifficulty, count: int, percentage: int}>  $difficulties  Every difficulty option, hardest parts first and "Tidak ada" last.
     * @param  array<int, array{module_id: int, title: string, total: int, average_score: float, low_score_count: int, top_difficulty: FeedbackDifficulty|null}>  $modules  Modules ranked by lowest average helpfulness.
     * @param  array<int, array{mentee: string, module: string, score: int, difficulty: FeedbackDifficulty|null, reason: string|null, open_answer: string|null, sent_at: string}>  $recent_low_scores  Latest feedback scored 1-3.
     */
    public function __construct(
        public int $total,
        public int $mentees,
        public ?float $average_score,
        public int $low_score_count,
        public array $score_distribution,
        public array $difficulties,
        public array $modules,
        public array $recent_low_scores,
    ) {}

    /**
     * Share of feedback scored 1-3, rounded to a whole percent.
     */
    public function lowScorePercentage(): int
    {
        return $this->total === 0 ? 0 : (int) round($this->low_score_count / $this->total * 100);
    }
}
