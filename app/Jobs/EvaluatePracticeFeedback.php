<?php

namespace App\Jobs;

use App\Contracts\PracticeSubmissionServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

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
     * Never retry automatically: a failed evaluation is surfaced to the
     * mentee, who retries explicitly instead of the provider being billed
     * again without the mentee's knowledge.
     */
    public int $tries = 1;

    /**
     * A stuck evaluation process must not block a queue slot indefinitely.
     *
     * Must stay below the queue connection's `retry_after` (90s for the
     * database queue in config/queue.php); otherwise a still-running job is
     * reserved again by another worker and fails with "max attempts exceeded"
     * while the first run is still writing the feedback. The agent call itself
     * is capped at 25s by #[Timeout] on PracticeFeedbackAgent.
     */
    public int $timeout = 60;

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
}
