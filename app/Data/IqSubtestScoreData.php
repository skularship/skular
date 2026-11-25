<?php

namespace App\Data;

use Spatie\LaravelData\Data;

/**
 * Score of one Tes IQ subtest ("Numerik 5/7 (71%)"), paid access only.
 */
class IqSubtestScoreData extends Data
{
    /**
     * @param  string  $subtest  IqSubtest value.
     * @param  string  $label  Subtest label.
     * @param  int  $correct  Correct answers.
     * @param  int  $total  Questions of the subtest in the attempt.
     * @param  int  $percent  Rounded percent.
     */
    public function __construct(
        public string $subtest,
        public string $label,
        public int $correct,
        public int $total,
        public int $percent,
    ) {}
}
