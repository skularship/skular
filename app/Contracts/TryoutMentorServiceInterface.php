<?php

namespace App\Contracts;

use App\Data\MentorReplyData;
use App\Exceptions\AiMentorException;
use App\Exceptions\TryoutReviewLockedException;
use App\Models\TryoutAttempt;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Answers Pro mentee questions about a finished Try Out with TryoutMentorAgent.
 * The quota is enforced by the caller with TryoutMentorQuotaServiceInterface.
 */
interface TryoutMentorServiceInterface
{
    /**
     * Answer a question, optionally about one answered question of the attempt.
     *
     * @param  array<int, array<string, mixed>>  $history  Previous chat turns.
     *
     * @throws TryoutReviewLockedException When the mentee is not on Pro.
     * @throws ValidationException When the question is empty, too long, or about an unanswered question.
     * @throws AiMentorException When the model fails.
     */
    public function ask(User $user, TryoutAttempt $attempt, string $question, ?string $question_code = null, array $history = []): MentorReplyData;
}
