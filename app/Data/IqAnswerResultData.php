<?php

namespace App\Data;

use Spatie\LaravelData\Data;

/**
 * Outcome of saving one Tes IQ pick: the answered count for the progress
 * bar and, at the 25/50/75% milestones, a support message built only from
 * the mentee's own counts (never correctness, praise or comparisons).
 */
class IqAnswerResultData extends Data
{
    /**
     * @param  int  $answered_count  Questions with a saved choice.
     * @param  int  $total  Questions in the attempt.
     * @param  string|null  $support_message  Message to show briefly, or null.
     */
    public function __construct(
        public int $answered_count,
        public int $total,
        public ?string $support_message = null,
    ) {}
}
