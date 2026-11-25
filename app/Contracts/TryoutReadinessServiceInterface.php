<?php

namespace App\Contracts;

use App\Models\Tryout;

/**
 * Checks whether a Try Out may be published (subtest order and pool size).
 */
interface TryoutReadinessServiceInterface
{
    /**
     * Everything that still blocks publishing, as Indonesian sentences.
     *
     * @return array<int, string> Empty when the Try Out may be published.
     */
    public function problems(Tryout $tryout): array;
}
