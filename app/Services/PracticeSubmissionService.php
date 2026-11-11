<?php

namespace App\Services;

use App\Concerns\PracticeFieldRules;
use App\Contracts\AiFeedbackServiceInterface;
use App\Contracts\PracticeSubmissionServiceInterface;
use App\Data\AiFeedbackData;
use App\Data\PracticeFieldData;
use App\Enums\AiFailureReason;
use App\Enums\AiFeedbackStatus;
use App\Enums\PracticeFieldType;
use App\Exceptions\AiFeedbackException;
use App\Jobs\EvaluatePracticeFeedback;
use App\Models\Module;
use App\Models\PracticeForm;
use App\Models\User;
use App\Models\UserPracticeResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Sleep;

/**
 * Validates and stores practice answers, then evaluates them with AI in the
 * queue (EvaluatePracticeFeedback). The first evaluation and "Nilai ulang"
 * share that one queued path. One row per mentee per practice form; a
 * resubmission updates that row and increments attempts.
 */
class PracticeSubmissionService implements PracticeSubmissionServiceInterface
{
    use PracticeFieldRules;

    /**
     * Provider calls the queued evaluation makes before showing a failure.
     *
     * The first call after a submission is the one most likely to hit a
     * passing timeout or rate limit; one silent retry spares the mentee from
     * pressing "Nilai ulang" for a hiccup. Two agent calls of
     * PracticeFeedbackAgent::TIMEOUT_SECONDS plus the longest pause stay
     * below EvaluatePracticeFeedback::$timeout.
     */
    public const QUEUED_EVALUATION_ATTEMPTS = 2;

    /**
     * Pause range in milliseconds before retrying a transient failure.
     *
     * The random jitter keeps evaluations that failed together from hitting
     * the provider again at the same moment.
     *
     * @var array{int, int}
     */
    public const RETRY_PAUSE_MS = [1500, 3500];

    /**
     * Longer pause range in milliseconds after a rate limit or overloaded provider.
     *
     * @var array{int, int}
     */
    public const RATE_LIMITED_PAUSE_MS = [5000, 8000];

    /**
     * Lifetime in seconds of the per-submission evaluation lock.
     *
     * At least the job timeout, so a manual retry cannot start a parallel
     * provider call while the job is still retrying, and at most
     * UserPracticeResponse::STALLED_AFTER_SECONDS, so a lock left by a dead
     * worker has expired by the time the mentee is offered "Nilai ulang".
     */
    public const EVALUATION_LOCK_SECONDS = 110;

    /**
     * How many times one evaluation re-grades answers that were replaced while
     * the provider was answering, before leaving them to their own job.
     */
    private const SUPERSEDED_REEVALUATIONS = 1;

    /**
     * @param  AiFeedbackServiceInterface  $ai_feedback_service  Grades a submission with the AI evaluator.
     */
    public function __construct(
        private readonly AiFeedbackServiceInterface $ai_feedback_service,
    ) {}

    /**
     * Validate and store a practice submission, then queue the AI feedback.
     *
     * The submission itself is durable before the response leaves the web
     * request: the answers are saved in one transaction, then the evaluation
     * runs in the queue after the commit. That is what keeps the practice
     * save instant even when the provider is slow.
     *
     * @param  array<string, mixed>  $answers
     */
    public function submit(User $user, PracticeForm $practice_form, array $answers): UserPracticeResponse
    {
        $response = DB::transaction(function () use ($user, $practice_form, $answers): UserPracticeResponse {
            // firstOrCreate + the unique (user_id, practice_form_id) index makes
            // concurrent submissions converge on one row instead of creating
            // duplicates. The row lock then serialises their updates.
            $response = UserPracticeResponse::query()->firstOrCreate(
                [
                    'user_id' => $user->id,
                    'practice_form_id' => $practice_form->id,
                ],
                [
                    'module_id' => $practice_form->module_id,
                    'answers' => [],
                    'ai_status' => AiFeedbackStatus::Pending,
                    'attempts' => 0,
                ],
            );

            $response = UserPracticeResponse::query()
                ->whereKey($response->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            [$answers, $stored_files] = $this->extractStoredFiles($practice_form, $response, $answers);

            $validated = $this->validateAnswers($user, $practice_form, $answers, array_keys($stored_files));

            $validated = $stored_files + $validated;

            $response->fill([
                'module_id' => $practice_form->module_id,
                'answers' => $validated,
                'submitted_at' => now(),
                'attempts' => $response->attempts + 1,
            ]);

            $response->ai_status = AiFeedbackStatus::Processing;
            $response->ai_error = null;
            $response->save();

            return $response;
        });

        EvaluatePracticeFeedback::dispatch($response->getKey(), $practice_form->id, $response->attempts);

        return $response;
    }

    /**
     * Evaluate a stored submission from the background job.
     *
     * Skipped when the submission is no longer waiting for an evaluation
     * (completed or failed), so a replayed job can never overwrite feedback
     * nor bill the provider twice, and when $attempt belongs to an older
     * submission, whose newer answers have their own job.
     *
     * @param  int|null  $attempt  The `attempts` value the job was dispatched for; null for jobs queued before this field existed.
     */
    public function evaluateSubmission(int $response_id, int $practice_form_id, ?int $attempt = null): void
    {
        /** @var UserPracticeResponse|null $response */
        $response = UserPracticeResponse::query()->find($response_id);

        $practice_form = PracticeForm::query()->find($practice_form_id);

        if ($response === null || $practice_form === null) {
            return;
        }

        if (! $this->isAwaitingEvaluation($response)) {
            return;
        }

        if ($attempt !== null && $response->attempts !== $attempt) {
            return;
        }

        // How long the job waited for a worker: a large value points at a
        // missing or overloaded queue worker rather than at the provider.
        Log::info('ai.feedback.job_started', [
            'ai_response_id' => $response->id,
            'queue_wait_ms' => $response->updated_at === null ? null : (int) $response->updated_at->diffInMilliseconds(now()),
        ]);

        $this->requestFeedback($response, $practice_form, self::QUEUED_EVALUATION_ATTEMPTS, 'queue');
    }

    /**
     * Queue a new AI evaluation for a stored submission ("Nilai ulang").
     *
     * Uses the same queued job and attempt budget as the first evaluation, so
     * the web request never waits for the provider and both clicks behave the
     * same. Nothing changes when the evaluation cannot be retried or another
     * process still holds the evaluation lock: the response is returned as
     * stored, so it never claims an evaluation that no process will finish.
     */
    public function retryAiFeedback(UserPracticeResponse $response): UserPracticeResponse
    {
        if (! $response->canRetryEvaluation() || $this->isEvaluationRunning($response)) {
            return $response;
        }

        $response->ai_status = AiFeedbackStatus::Processing;
        $response->ai_error = null;
        // Saving moves updated_at, which restarts the stall window.
        $response->save();

        EvaluatePracticeFeedback::dispatch($response->getKey(), $response->practice_form_id, $response->attempts);

        return $response->refresh();
    }

    /**
     * Determine whether a process currently holds the evaluation lock of a submission.
     */
    public function isEvaluationRunning(UserPracticeResponse $response): bool
    {
        $lock = Cache::lock(self::lockKey($response), self::EVALUATION_LOCK_SECONDS);

        if (! $lock->get()) {
            return true;
        }

        $lock->release();

        return false;
    }

    /**
     * Ask the AI evaluator for structured feedback and persist the outcome.
     *
     * A transient failure (timeout, rate limit, unusable answer) is retried up
     * to $max_attempts calls in total; a configuration error fails at once.
     * Only the final outcome is stored, so the mentee sees "failed" only when
     * every attempt failed.
     *
     * @param  'queue'  $trigger  Which path started the evaluation; written to the ai.feedback.attempt log.
     */
    private function requestFeedback(UserPracticeResponse $response, PracticeForm $practice_form, int $max_attempts, string $trigger): UserPracticeResponse
    {
        // A duplicated job must not pay the provider twice for the same
        // submission. The process holding the lock stores the outcome itself,
        // so the second caller changes nothing and leaves the state as it is.
        $lock = Cache::lock(self::lockKey($response), self::EVALUATION_LOCK_SECONDS);

        if (! $lock->get()) {
            return $response;
        }

        try {
            $module = $this->moduleOf($practice_form);

            for ($round = 0; $round <= self::SUPERSEDED_REEVALUATIONS; $round++) {
                $outcome = $this->evaluateWithRetries($response, $module, $practice_form, $max_attempts, $trigger);

                $stored = $this->storeOutcome($response, $outcome);

                if ($stored !== null) {
                    return $stored;
                }

                // The mentee resubmitted while the provider was answering, so
                // the outcome belongs to answers that no longer exist. The
                // newer answers are graded here, still inside the lock: their
                // own job may already have found the lock taken and stopped.
                $response->refresh();

                if (! $this->isAwaitingEvaluation($response)) {
                    return $response;
                }
            }

            return $response;
        } finally {
            $lock->release();
        }
    }

    /**
     * Call the evaluator until it succeeds, fails permanently, or the attempts run out.
     *
     * @param  'queue'  $trigger
     * @return array{ai_feedback?: array<string, mixed>, ai_score?: int, ai_status: AiFeedbackStatus, ai_error: string|null}
     */
    private function evaluateWithRetries(UserPracticeResponse $response, Module $module, PracticeForm $practice_form, int $max_attempts, string $trigger): array
    {
        for ($attempt = 1; ; $attempt++) {
            $this->touchHeartbeat($response);

            try {
                $feedback = Context::scope(
                    fn (): AiFeedbackData => $this->ai_feedback_service->generate($module, $practice_form, $response->answers),
                    ['ai_response_id' => $response->id, 'ai_attempt' => $attempt, 'ai_trigger' => $trigger],
                );

                return [
                    'ai_feedback' => $feedback->toArray(),
                    'ai_score' => $feedback->normalizedScore(),
                    'ai_status' => AiFeedbackStatus::Completed,
                    'ai_error' => null,
                ];
            } catch (AiFeedbackException $exception) {
                if ($attempt < $max_attempts && $exception->isTransient()) {
                    Sleep::for($this->retryPauseMilliseconds($exception))->milliseconds();

                    continue;
                }

                report($exception);

                return [
                    'ai_status' => AiFeedbackStatus::Failed,
                    'ai_error' => $exception->getMessage(),
                ];
            }
        }
    }

    /**
     * Store an evaluation outcome unless a newer submission replaced the graded answers.
     *
     * The row is locked and its `attempts` compared with the graded version,
     * so feedback for old answers can never be shown as the result of new ones.
     * Only the ai_* columns are written; the answers are never touched.
     *
     * @param  array<string, mixed>  $outcome
     * @return UserPracticeResponse|null The stored response, or null when the graded answers were superseded.
     */
    private function storeOutcome(UserPracticeResponse $response, array $outcome): ?UserPracticeResponse
    {
        return DB::transaction(function () use ($response, $outcome): ?UserPracticeResponse {
            $current = UserPracticeResponse::query()->whereKey($response->getKey())->lockForUpdate()->first();

            if ($current === null) {
                return $response;
            }

            if ($current->attempts !== $response->attempts) {
                return null;
            }

            $current->fill($outcome)->save();

            return $current;
        });
    }

    /**
     * Determine whether a submission still waits for an evaluation outcome.
     */
    private function isAwaitingEvaluation(UserPracticeResponse $response): bool
    {
        return in_array($response->ai_status, [AiFeedbackStatus::Pending, AiFeedbackStatus::Processing], true);
    }

    /**
     * Cache key of the per-submission evaluation lock.
     */
    private static function lockKey(UserPracticeResponse $response): string
    {
        return 'ai-feedback:'.$response->getKey();
    }

    /**
     * Pick a jittered pause before the next attempt; rate limits wait longer.
     */
    private function retryPauseMilliseconds(AiFeedbackException $exception): int
    {
        [$min, $max] = $exception->reason === AiFailureReason::RateLimited
            ? self::RATE_LIMITED_PAUSE_MS
            : self::RETRY_PAUSE_MS;

        return random_int($min, $max);
    }

    /**
     * Mark a running evaluation as alive by touching updated_at.
     *
     * UserPracticeResponse::isStalled() measures from updated_at, so a
     * heartbeat at every attempt is what allows a short stall window without
     * flagging a slow but healthy evaluation. Only the timestamp is written;
     * the answers are never touched.
     */
    private function touchHeartbeat(UserPracticeResponse $response): void
    {
        $now = now();

        UserPracticeResponse::query()->whereKey($response->getKey())->update(['updated_at' => $now]);

        $response->updated_at = $now->toImmutable();
        $response->syncOriginalAttribute('updated_at');
    }

    /**
     * Validate the submitted answers against the dynamic form schema and store uploads.
     *
     * @param  array<string, mixed>  $answers
     * @param  array<int, string>  $answered
     * @return array<string, mixed>
     */
    private function validateAnswers(User $user, PracticeForm $practice_form, array $answers, array $answered = []): array
    {
        $validated = Validator::make(
            ['answers' => $answers],
            $this->practiceFieldRules($practice_form, $answered),
            [],
            $this->practiceFieldAttributes($practice_form),
        )->validate();

        /** @var array<string, mixed> $clean */
        $clean = is_array($validated['answers'] ?? null) ? $validated['answers'] : [];

        return $this->storeFileAnswers($user, $practice_form, $clean);
    }

    /**
     * Take the file answers already stored on a previous submission out of the
     * payload so a mentee can resubmit without re-uploading the same file.
     *
     * @param  array<string, mixed>  $answers
     * @return array{array<string, mixed>, array<string, string>}
     */
    private function extractStoredFiles(PracticeForm $practice_form, UserPracticeResponse $response, array $answers): array
    {
        $stored = [];

        foreach (PracticeFieldData::collectFromSchema($practice_form->fields ?? []) as $field) {
            if ($field->type !== PracticeFieldType::File) {
                continue;
            }

            if (($answers[$field->name] ?? null) instanceof UploadedFile) {
                continue;
            }

            unset($answers[$field->name]);

            $previous_answers = $response->exists ? $response->answers : [];
            $previous = $previous_answers[$field->name] ?? null;

            if (is_string($previous) && $previous !== '') {
                $stored[$field->name] = $previous;
            }
        }

        return [$answers, $stored];
    }

    /**
     * Move uploaded file answers to the private disk and keep the stored path.
     *
     * @param  array<string, mixed>  $answers
     * @return array<string, mixed>
     */
    private function storeFileAnswers(User $user, PracticeForm $practice_form, array $answers): array
    {
        foreach (PracticeFieldData::collectFromSchema($practice_form->fields ?? []) as $field) {
            if ($field->type !== PracticeFieldType::File) {
                continue;
            }

            $value = $answers[$field->name] ?? null;

            if (! $value instanceof UploadedFile) {
                continue;
            }

            $path = $value->store(
                'practice/'.$user->id,
                'local',
            );

            $answers[$field->name] = is_string($path) ? $path : null;
        }

        return $answers;
    }

    /**
     * Resolve the module a practice form belongs to.
     */
    private function moduleOf(PracticeForm $practice_form): Module
    {
        return $practice_form->module()->firstOrFail();
    }
}
