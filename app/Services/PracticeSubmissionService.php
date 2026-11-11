<?php

namespace App\Services;

use App\Concerns\PracticeFieldRules;
use App\Contracts\AiFeedbackServiceInterface;
use App\Contracts\PracticeSubmissionServiceInterface;
use App\Data\PracticeFieldData;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Sleep;

/**
 * Validates and stores practice answers, then evaluates them with AI in the
 * queue (EvaluatePracticeFeedback). One row per mentee per practice form; a
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
     * pressing "Nilai ulang" for a hiccup. Two 25-second agent calls plus the
     * pause stay below the job's 60-second timeout.
     */
    public const QUEUED_EVALUATION_ATTEMPTS = 2;

    /**
     * Pause in seconds between two queued evaluation attempts.
     */
    public const RETRY_PAUSE_SECONDS = 3;

    /**
     * Lifetime in seconds of the per-submission evaluation lock.
     *
     * Covers every queued attempt so a manual retry cannot start a parallel
     * provider call while the job is still retrying.
     */
    private const EVALUATION_LOCK_SECONDS = 75;

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

        EvaluatePracticeFeedback::dispatch($response->getKey(), $practice_form->id);

        return $response;
    }

    /**
     * Evaluate a stored submission from the background job.
     *
     * A submission that already has feedback is skipped, so a replayed job can
     * never overwrite a completed evaluation nor bill the provider twice.
     */
    public function evaluateSubmission(int $response_id, int $practice_form_id): void
    {
        /** @var UserPracticeResponse|null $response */
        $response = UserPracticeResponse::query()->find($response_id);

        $practice_form = PracticeForm::query()->find($practice_form_id);

        if ($response === null || $practice_form === null) {
            return;
        }

        if ($response->ai_status === AiFeedbackStatus::Completed) {
            return;
        }

        $this->requestFeedback($response, $practice_form, self::QUEUED_EVALUATION_ATTEMPTS);
    }

    /**
     * Request AI feedback again for a stored submission.
     *
     * This stays synchronous: the mentee pressed an explicit retry button and
     * expects the outcome, and the shared provider lock below still prevents
     * duplicated provider calls.
     */
    public function retryAiFeedback(UserPracticeResponse $response): UserPracticeResponse
    {
        $practice_form = $response->practiceForm()->firstOrFail();

        $response->ai_status = AiFeedbackStatus::Processing;
        $response->ai_error = null;
        $response->save();

        return $this->requestFeedback($response, $practice_form);
    }

    /**
     * Ask the AI evaluator for structured feedback and persist the outcome.
     *
     * A transient failure (timeout, rate limit, empty evaluation) is retried
     * up to $max_attempts calls in total; a configuration error fails at once.
     * Only the final outcome is stored, so the mentee sees "failed" only when
     * every attempt failed.
     */
    private function requestFeedback(UserPracticeResponse $response, PracticeForm $practice_form, int $max_attempts = 1): UserPracticeResponse
    {
        // A duplicated click or two parallel requests must not pay the provider
        // twice for the same submission. The second caller keeps the stored
        // answers and leaves the evaluation to the request holding the lock.
        $lock = Cache::lock('ai-feedback:'.$response->getKey(), self::EVALUATION_LOCK_SECONDS);

        if (! $lock->get()) {
            return $response;
        }

        try {
            $module = $this->moduleOf($practice_form);

            for ($attempt = 1; ; $attempt++) {
                try {
                    $feedback = $this->ai_feedback_service->generate(
                        $module,
                        $practice_form,
                        $response->answers,
                    );

                    $response->fill([
                        'ai_feedback' => $feedback->toArray(),
                        'ai_score' => $feedback->normalizedScore(),
                        'ai_status' => AiFeedbackStatus::Completed,
                        'ai_error' => null,
                    ]);

                    break;
                } catch (AiFeedbackException $exception) {
                    if ($attempt < $max_attempts && $exception->isTransient()) {
                        Sleep::for(self::RETRY_PAUSE_SECONDS)->seconds();

                        continue;
                    }

                    report($exception);

                    $response->fill([
                        'ai_status' => AiFeedbackStatus::Failed,
                        'ai_error' => $exception->getMessage(),
                    ]);

                    break;
                }
            }

            $response->save();
        } finally {
            $lock->release();
        }

        return $response;
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
