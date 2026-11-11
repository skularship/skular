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
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Validates and stores practice answers, then evaluates them with AI
 * synchronously in the same request. The first evaluation and "Nilai ulang"
 * share that one path, guarded by a per-submission cache lock. One row per
 * mentee per practice form; a resubmission updates that row and increments
 * attempts.
 */
class PracticeSubmissionService implements PracticeSubmissionServiceInterface
{
    use PracticeFieldRules;

    /**
     * Shortest wall-clock limit, in seconds, that a web request may hit in front of PHP.
     *
     * The evaluation runs inside the Livewire request, so it has to finish
     * before the web server gives up on that request. 60 seconds is the
     * nginx default for fastcgi_read_timeout and proxy_read_timeout, the
     * smallest limit among the usual front servers (Apache and LiteSpeed
     * default to 300s, Cloudflare to 100s). A deployment with a shorter limit
     * must lower the evaluation budget first.
     */
    public const REQUEST_TIME_LIMIT_SECONDS = 60;

    /**
     * Seconds kept free under REQUEST_TIME_LIMIT_SECONDS for everything that is
     * not a provider call: validation, database writes, rendering the
     * Livewire response and network latency.
     */
    public const REQUEST_SAFETY_MARGIN_SECONDS = 10;

    /**
     * Provider calls one evaluation makes before showing a failure.
     *
     * The first call is the one most likely to hit a passing timeout or rate
     * limit; one silent retry spares the mentee from pressing "Nilai ulang"
     * for a hiccup. Worst case, two agent calls of
     * PracticeFeedbackAgent::TIMEOUT_SECONDS plus the longest pause must fit in
     * REQUEST_TIME_LIMIT_SECONDS minus REQUEST_SAFETY_MARGIN_SECONDS.
     */
    public const EVALUATION_ATTEMPTS = 2;

    /**
     * Pause range in milliseconds before retrying a transient failure.
     *
     * The random jitter keeps evaluations that failed together from hitting
     * the provider again at the same moment.
     *
     * @var array{int, int}
     */
    public const RETRY_PAUSE_MS = [1000, 2500];

    /**
     * Longer pause range in milliseconds after a rate limit or overloaded provider.
     *
     * Its upper bound is the longest pause of the evaluation time budget.
     *
     * @var array{int, int}
     */
    public const RATE_LIMITED_PAUSE_MS = [3000, 5000];

    /**
     * Lifetime in seconds of the per-submission evaluation lock.
     *
     * At least the worst-case evaluation, so a double click or a second tab
     * cannot start a parallel provider call while the first one is still
     * retrying, and equal to REQUEST_TIME_LIMIT_SECONDS: a lock left by a
     * request the web server killed has expired by the time the mentee is
     * offered "Nilai ulang" (UserPracticeResponse::STALLED_AFTER_SECONDS).
     */
    public const EVALUATION_LOCK_SECONDS = 60;

    /**
     * Message shown when an evaluation stopped on an unexpected error.
     */
    public const ABORTED_EVALUATION_MESSAGE = 'Penilaian AI terhenti sebelum selesai. Jawabanmu tetap tersimpan, coba nilai ulang ya.';

    /**
     * How many times one evaluation re-grades answers that were replaced while
     * the provider was answering. The request that stored the newer answers
     * found the lock taken and left them to the lock holder.
     */
    private const SUPERSEDED_REEVALUATIONS = 1;

    /**
     * @param  AiFeedbackServiceInterface  $ai_feedback_service  Grades a submission with the AI evaluator.
     */
    public function __construct(
        private readonly AiFeedbackServiceInterface $ai_feedback_service,
    ) {}

    /**
     * Validate and store a practice submission, then evaluate it with AI in the same request.
     *
     * The answers are saved and committed in one transaction before the
     * provider is called, so the mentee keeps their work even when the AI
     * call is slow, fails, or the request dies halfway.
     *
     * @param  array<string, mixed>  $answers
     * @return UserPracticeResponse Completed or Failed; Processing only when another request holds the evaluation lock.
     *
     * @throws ValidationException When the answers do not match the form schema.
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

        return $this->evaluate($response, 'submit', false);
    }

    /**
     * Evaluate a stored submission again ("Nilai ulang").
     *
     * Uses the same synchronous path and attempt budget as the first
     * evaluation. Nothing changes when the evaluation cannot be retried or
     * another request still holds the evaluation lock: the response is
     * returned as stored, so it never claims an evaluation that no request
     * will finish.
     *
     * @return UserPracticeResponse The submission with its new (or unchanged) evaluation outcome.
     */
    public function retryAiFeedback(UserPracticeResponse $response): UserPracticeResponse
    {
        if (! $response->canRetryEvaluation()) {
            return $response;
        }

        return $this->evaluate($response, 'retry', true);
    }

    /**
     * Determine whether a request currently holds the evaluation lock of a submission.
     *
     * @return bool True while another evaluation of this submission is running.
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
     * Run one guarded evaluation and always leave the submission in a final state.
     *
     * The cache lock keeps a double click or a second tab from paying the
     * provider twice: the caller that finds it taken changes nothing. Any
     * error that escapes the evaluation (not only AiFeedbackException) fails
     * the graded answer version with a friendly message instead of leaving
     * it Processing, and the lock is always released.
     *
     * @param  'submit'|'retry'  $trigger  Which action started the evaluation; written to the ai.feedback.attempt log.
     * @param  bool  $restart  Move a failed or stalled submission back to Processing first ("Nilai ulang").
     */
    private function evaluate(UserPracticeResponse $response, string $trigger, bool $restart): UserPracticeResponse
    {
        $lock = Cache::lock(self::lockKey($response), self::EVALUATION_LOCK_SECONDS);

        if (! $lock->get()) {
            return $response;
        }

        try {
            $this->ensureExecutionTimeForEvaluation();

            if ($restart) {
                // Another request may have finished the evaluation after the
                // mentee saw the retry button.
                $response->refresh();

                if (! $response->canRetryEvaluation()) {
                    return $response;
                }

                $response->ai_status = AiFeedbackStatus::Processing;
                $response->ai_error = null;
                // Saving moves updated_at, which restarts the stall window.
                $response->save();
            }

            return $this->gradeLatestAnswers($response, $trigger);
        } catch (Throwable $exception) {
            report($exception);

            $this->failAbortedEvaluation($response, $exception);

            return $response->fresh() ?? $response;
        } finally {
            $lock->release();
        }
    }

    /**
     * Grade the stored answers and keep grading while a resubmission replaces them.
     *
     * Runs inside the evaluation lock. When the mentee resubmits while the
     * provider is answering, the outcome belongs to answers that no longer
     * exist: it is dropped and the newer answers are graded here, because
     * the request that stored them found the lock taken and stopped.
     *
     * @param  'submit'|'retry'  $trigger
     */
    private function gradeLatestAnswers(UserPracticeResponse $response, string $trigger): UserPracticeResponse
    {
        $practice_form = $response->practiceForm()->firstOrFail();
        $module = $this->moduleOf($practice_form);

        for ($round = 0; $round <= self::SUPERSEDED_REEVALUATIONS; $round++) {
            $outcome = $this->evaluateWithRetries($response, $module, $practice_form, self::EVALUATION_ATTEMPTS, $trigger);

            $stored = $this->storeOutcome($response, $outcome);

            if ($stored !== null) {
                return $stored;
            }

            $response->refresh();

            if (! $this->isAwaitingEvaluation($response)) {
                return $response;
            }
        }

        return $response;
    }

    /**
     * Fail the graded answer version after an unexpected error stopped its evaluation.
     *
     * Only a Pending or Processing row of the same `attempts` is touched: a
     * completed outcome is never overwritten, and a newer submission is not
     * failed for an error that happened while grading older answers. Only
     * metadata is logged, never the answers.
     */
    private function failAbortedEvaluation(UserPracticeResponse $response, Throwable $exception): void
    {
        Log::warning('ai.feedback.evaluation_aborted', [
            'ai_response_id' => $response->getKey(),
            'attempt' => $response->attempts,
            'exception' => $exception::class,
        ]);

        UserPracticeResponse::query()
            ->whereKey($response->getKey())
            ->whereIn('ai_status', [AiFeedbackStatus::Pending->value, AiFeedbackStatus::Processing->value])
            ->where('attempts', $response->attempts)
            ->update([
                'ai_status' => AiFeedbackStatus::Failed->value,
                'ai_error' => self::ABORTED_EVALUATION_MESSAGE,
                'updated_at' => now(),
            ]);
    }

    /**
     * Give the request enough PHP execution time for a worst-case evaluation.
     *
     * Only raises a finite max_execution_time that is lower than
     * REQUEST_TIME_LIMIT_SECONDS (where PHP counts wall-clock time, a 30s
     * default would kill a slow evaluation midway); an unlimited setting
     * (0, as in the CLI) is left alone.
     */
    private function ensureExecutionTimeForEvaluation(): void
    {
        $limit = (int) ini_get('max_execution_time');

        if ($limit === 0 || $limit >= self::REQUEST_TIME_LIMIT_SECONDS || ! function_exists('set_time_limit')) {
            return;
        }

        set_time_limit(self::REQUEST_TIME_LIMIT_SECONDS);
    }

    /**
     * Call the evaluator until it succeeds, fails permanently, or the attempts run out.
     *
     * @param  'submit'|'retry'  $trigger
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
