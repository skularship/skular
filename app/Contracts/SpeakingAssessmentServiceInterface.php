<?php

namespace App\Contracts;

use App\Models\TryoutAttemptSection;

/**
 * Grades the whole Speaking subtest from the browser-made transcripts of its parts (one call).
 */
interface SpeakingAssessmentServiceInterface
{
    /**
     * Grade the Speaking subtest from the transcripts of its parts.
     */
    public function assess(TryoutAttemptSection $attempt_section): TryoutAttemptSection;

    /**
     * Grade a closed Speaking subtest that still waits for its assessment.
     *
     * @return bool True when the assessment ran in this call.
     */
    public function nextStep(TryoutAttemptSection $attempt_section): bool;

    /**
     * Grade a failed or stalled Speaking assessment again ("Nilai ulang").
     */
    public function retry(TryoutAttemptSection $attempt_section): TryoutAttemptSection;
}
