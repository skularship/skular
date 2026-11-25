<?php

namespace App\Contracts;

use App\Data\IqAnswerResultData;
use App\Data\IqQuestionViewData;
use App\Exceptions\IqAnswerRejectedException;
use App\Exceptions\IqNotAvailableException;
use App\Models\IqAttempt;
use App\Models\IqTest;
use App\Models\User;

/**
 * Runs a Tes IQ attempt on server time: one active attempt per account,
 * the question set frozen at start, every pick saved at once (and
 * changeable until the end), time per question measured on the server,
 * and the scores computed on the server when the attempt closes.
 */
interface IqAttemptServiceInterface
{
    /**
     * The published test mentees take now (the newest one), or null.
     */
    public function currentTest(): ?IqTest;

    /**
     * The mentee's attempt in progress, after closing it when its time ran out.
     */
    public function activeAttempt(User $user): ?IqAttempt;

    /**
     * Start an attempt, or return the one already in progress.
     *
     * @throws IqNotAvailableException When the test is a draft or has no active questions.
     */
    public function start(User $user, IqTest $test): IqAttempt;

    /**
     * Show the question at a position and record the time spent on the previous one.
     *
     * @param  int  $position  1-based place in the attempt; clamped to the attempt.
     */
    public function question(IqAttempt $attempt, int $position): IqQuestionViewData;

    /**
     * Save (or change) the choice of the question at a position.
     *
     * @throws IqAnswerRejectedException When the attempt is closed, the time ran out, or the input is invalid.
     */
    public function answer(IqAttempt $attempt, int $position, string $choice): IqAnswerResultData;

    /**
     * Positions (1-based) that have a saved choice.
     *
     * @return array<int, int>
     */
    public function answeredPositions(IqAttempt $attempt): array;

    /**
     * Close the attempt on "Kirim" (or as expired when the time already ran out) and score it.
     */
    public function finish(IqAttempt $attempt): void;

    /**
     * Close and score the attempt when its server deadline passed.
     *
     * @return bool True when this call closed it.
     */
    public function closeIfExpired(IqAttempt $attempt): bool;
}
