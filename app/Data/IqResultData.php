<?php

namespace App\Data;

use Spatie\LaravelData\Data;

/**
 * Result of a closed Tes IQ attempt. Locked (no paid access): only the
 * answered count; every score field stays null so nothing reaches the page.
 * Never carries an IQ number.
 */
class IqResultData extends Data
{
    /**
     * @param  bool  $unlocked  Paid access: scores are filled.
     * @param  string  $status_label  "Selesai" or "Waktu habis".
     * @param  int  $answered_count  Questions answered.
     * @param  int  $total_questions  Questions in the attempt.
     * @param  int|null  $correct_count  Correct answers (paid).
     * @param  int|null  $score_percent  Total percent (paid).
     * @param  string|null  $category  Category label (paid).
     * @param  array<int, IqSubtestScoreData>  $subtests  Per subtest (paid).
     */
    public function __construct(
        public bool $unlocked,
        public string $status_label,
        public int $answered_count,
        public int $total_questions,
        public ?int $correct_count = null,
        public ?int $score_percent = null,
        public ?string $category = null,
        public array $subtests = [],
    ) {}
}
