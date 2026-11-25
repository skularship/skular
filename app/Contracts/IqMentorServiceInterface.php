<?php

namespace App\Contracts;

use App\Data\MentorReplyData;
use App\Exceptions\AiMentorException;
use App\Exceptions\IqLockedException;
use App\Models\IqAttempt;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Answers questions of mentees with paid access about a closed Tes IQ with
 * IqMentorAgent. The daily quota is enforced by the caller with
 * IqMentorQuotaServiceInterface.
 */
interface IqMentorServiceInterface
{
    /**
     * Answer a question, optionally about one question of the attempt.
     *
     * @param  int|null  $position  1-based place of the asked question in the attempt.
     * @param  array<int, array<string, mixed>>  $history  Previous chat turns.
     *
     * @throws IqLockedException Without paid access.
     * @throws ValidationException When the question is empty, too long, or the position is invalid.
     * @throws AiMentorException When the model fails.
     */
    public function ask(User $user, IqAttempt $attempt, string $question, ?int $position = null, array $history = []): MentorReplyData;
}
