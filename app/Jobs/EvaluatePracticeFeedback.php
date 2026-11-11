<?php

namespace App\Jobs;

use App\Contracts\PracticeSubmissionServiceInterface;
use App\Enums\AiFeedbackStatus;
use App\Models\UserPracticeResponse;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Evaluate a stored practice submission with AI feedback off the web request.
 *
 * The submission row and its answers persist before this job runs, so the
 * mentee keeps their work even when the AI call is slow or failing; the
 * evaluation outcome (completed or failed status on the response) is the
 * single state the UI reads.
 */
final class EvaluatePracticeFeedback implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    /**
     * The queue never replays the job. A passing provider hiccup is retried
     * once inside the evaluation itself (PracticeSubmissionService::
     * QUEUED_EVALUATION_ATTEMPTS); anything that still fails is surfaced to
     * the mentee, who retries explicitly with "Nilai ulang".
     */
    public int $tries = 1;

    /**
     * A stuck evaluation process must not block a queue slot indefinitely.
     *
     * Covers every queued attempt: 2 agent calls of
     * PracticeFeedbackAgent::TIMEOUT_SECONDS (40s) plus the longest pause
     * (8s) is 88s. Must stay below the queue connection's `retry_after`
     * (150s for the database queue in config/queue.php); otherwise a
     * still-running job is reserved again by another worker and fails with
     * "max attempts exceeded" while the first run is still writing feedback.
     * Enforced only when the worker has the pcntl extension.
     */
    public int $timeout = 100;

    /**
     * Seconds the unique lock may outlive a worker that died mid-job.
     *
     * A worker killed without running failed() never releases the lock;
     * without this TTL the database cache kept it for a day and silently
     * dropped every later dispatch for the same submission. Kept at or below
     * UserPracticeResponse::STALLED_AFTER_SECONDS, so the evaluation the
     * mentee starts from "Nilai ulang" after a stall is always accepted.
     */
    public int $uniqueFor = 120;

    /**
     * One evaluation per stored response keeps duplicated clicks cheap.
     */
    public function uniqueId(): string
    {
        return (string) $this->response_id;
    }

    /**
     * Create a new evaluation job instance.
     *
     * The job runs only after the submission transaction has committed.
     */
    public function __construct(
        public readonly int $response_id,
        public readonly int $practice_form_id,
    ) {
        $this->afterCommit = true;
    }

    /**
     * Run the guarded evaluation through the submission service.
     */
    public function handle(PracticeSubmissionServiceInterface $submission_service): void
    {
        $submission_service->evaluateSubmission($this->response_id, $this->practice_form_id);
    }

    /**
     * Surface a crashed or timed-out evaluation as a retryable failure.
     *
     * Without this the response would stay "processing" forever: the mentee
     * would never see the retry button and could never reach the checkpoint.
     */
    public function failed(?Throwable $exception): void
    {
        Log::warning('AI Feedback job failed', [
            'response_id' => $this->response_id,
            'practice_form_id' => $this->practice_form_id,
            'exception' => $exception === null ? null : $exception::class,
            'message' => $exception?->getMessage(),
        ]);

        UserPracticeResponse::query()
            ->whereKey($this->response_id)
            ->whereIn('ai_status', [AiFeedbackStatus::Pending->value, AiFeedbackStatus::Processing->value])
            ->update([
                'ai_status' => AiFeedbackStatus::Failed->value,
                'ai_error' => 'Penilaian AI terhenti sebelum selesai. Jawabanmu tetap tersimpan, coba nilai ulang ya.',
                'updated_at' => now(),
            ]);
    }
}
