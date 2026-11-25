<?php

namespace App\Contracts;

use App\Data\TryoutAnswerResultData;
use App\Data\TryoutQuestionViewData;
use App\Exceptions\TryoutAnswerRejectedException;
use App\Exceptions\TryoutSectionExpiredException;
use App\Models\TryoutAnswer;
use App\Models\TryoutAttemptSection;
use Illuminate\Validation\ValidationException;

/**
 * Serves and stores answers of a Try Out subtest. An answer is stored before
 * anything else happens with it (AGENTS rule 9), and only for the active question.
 */
interface TryoutAnswerServiceInterface
{
    /**
     * The question served but not answered yet (resume), serving a new one when needed.
     *
     * @return TryoutQuestionViewData|null Null when the subtest is closed or the pool is exhausted.
     */
    public function currentQuestion(TryoutAttemptSection $attempt_section): ?TryoutQuestionViewData;

    /**
     * Every prompt of a Writing/Speaking subtest in task/part order.
     *
     * @return array<int, TryoutQuestionViewData>
     */
    public function productivePrompts(TryoutAttemptSection $attempt_section): array;

    /**
     * Store and check a Listening/Reading answer, move the level, serve the next question.
     *
     * @throws TryoutSectionExpiredException
     * @throws TryoutAnswerRejectedException
     */
    public function answerObjective(TryoutAttemptSection $attempt_section, string $code, string $response): TryoutAnswerResultData;

    /**
     * Autosave a Writing task.
     *
     * @throws TryoutSectionExpiredException
     * @throws TryoutAnswerRejectedException
     */
    public function saveEssay(TryoutAttemptSection $attempt_section, string $code, string $text): TryoutAnswer;

    /**
     * Store the browser-made transcript (Web Speech API) of a Speaking part; no audio is uploaded.
     *
     * @param  int  $seconds  How long the mentee spoke.
     *
     * @throws ValidationException
     * @throws TryoutSectionExpiredException
     * @throws TryoutAnswerRejectedException
     */
    public function saveSpokenAnswer(TryoutAttemptSection $attempt_section, string $code, string $transcript, int $seconds): TryoutAnswer;

    /**
     * Count one Listening audio play of the active unit when the mode still allows it.
     *
     * @return bool True when the audio may play.
     */
    public function registerAudioPlay(TryoutAttemptSection $attempt_section, string $unit_code): bool;
}
