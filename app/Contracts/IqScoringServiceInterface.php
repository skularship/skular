<?php

namespace App\Contracts;

use App\Models\IqAttempt;

/**
 * Scores a closed Tes IQ attempt on the server (model B): correct answers
 * per subtest and in total, as counts and rounded percentages, plus the
 * category from config/iq.php. Never computes an IQ number.
 */
interface IqScoringServiceInterface
{
    /**
     * Mark every answer against the current key and store the scores on the attempt.
     */
    public function score(IqAttempt $attempt): void;

    /**
     * Category label of a total percentage.
     */
    public function category(int $percent): string;
}
