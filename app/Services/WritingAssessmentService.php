<?php

namespace App\Services;

use App\Ai\Agents\WritingAssessmentAgent;
use App\Ai\AiConfiguration;
use App\Concerns\ExtractsStructuredResponse;
use App\Contracts\TryoutScoringServiceInterface;
use App\Contracts\WritingAssessmentServiceInterface;
use App\Data\CriterionBandData;
use App\Data\TryoutAssessmentData;
use App\Enums\AiFailureReason;
use App\Enums\AiFeedbackStatus;
use App\Enums\TryoutSectionStatus;
use App\Exceptions\TryoutAiException;
use App\Models\TryoutAnswer;
use App\Models\TryoutAttemptSection;
use App\Support\TryoutAiBudget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Grades one Writing task with WritingAssessmentAgent, synchronously in the
 * request and under a per-answer cache lock (same rules as the practice
 * evaluation, AGENTS rule 7). Any error fails only that task with a friendly
 * message and never overwrites a completed grade. An empty essay gets band 0
 * without calling the model.
 */
class WritingAssessmentService implements WritingAssessmentServiceInterface
{
    use ExtractsStructuredResponse;

    /**
     * @param  TryoutScoringServiceInterface  $scoring  Writing and overall band.
     */
    public function __construct(
        private readonly TryoutScoringServiceInterface $scoring,
    ) {}

    /**
     * Grade one Writing task synchronously under a per-answer lock.
     */
    public function assess(TryoutAnswer $answer): TryoutAnswer
    {
        $lock = Cache::lock(self::lockKey($answer), TryoutAiBudget::LOCK_SECONDS);

        if (! $lock->get()) {
            return $answer;
        }

        try {
            $answer->refresh();

            if ($answer->ai_status === AiFeedbackStatus::Completed) {
                return $answer;
            }

            TryoutAiBudget::ensureExecutionTime();

            $answer->forceFill([
                'ai_status' => AiFeedbackStatus::Processing,
                'ai_error' => null,
                'ai_attempts' => $answer->ai_attempts + 1,
            ])->save();

            $assessment = $this->grade($answer);

            $this->storeOutcome($answer, [
                'ai_status' => AiFeedbackStatus::Completed->value,
                'ai_assessment' => json_encode($assessment->toArray()),
                'band' => $assessment->band,
                'ai_error' => null,
            ]);
        } catch (Throwable $exception) {
            report($exception);

            $this->storeOutcome($answer, [
                'ai_status' => AiFeedbackStatus::Failed->value,
                'ai_error' => $exception instanceof TryoutAiException
                    ? $exception->getMessage()
                    : TryoutAiException::messageFor(AiFailureReason::Unavailable),
            ]);
        } finally {
            $lock->release();
        }

        $answer->refresh();
        $this->rescore($answer);

        return $answer;
    }

    /**
     * Grade a failed or stalled task again, within tryout.ai.max_retries_per_answer.
     */
    public function retry(TryoutAnswer $answer): TryoutAnswer
    {
        if (! $answer->canRetryAi()) {
            return $answer;
        }

        return $this->assess($answer);
    }

    /**
     * Grade the next task still waiting in a closed Writing subtest.
     */
    public function assessNext(TryoutAttemptSection $attempt_section): bool
    {
        if ($attempt_section->status !== TryoutSectionStatus::Submitted) {
            return false;
        }

        $next = $attempt_section->answers()
            ->where('ai_status', AiFeedbackStatus::Pending->value)
            ->first();

        if ($next === null) {
            return false;
        }

        $this->assess($next);

        return true;
    }

    /**
     * Whether a request currently grades this task.
     */
    public function isRunning(TryoutAnswer $answer): bool
    {
        $lock = Cache::lock(self::lockKey($answer), TryoutAiBudget::LOCK_SECONDS);

        if (! $lock->get()) {
            return true;
        }

        $lock->release();

        return false;
    }

    /**
     * Call the model (or score an empty essay 0) and read the four criteria.
     *
     * @throws TryoutAiException
     */
    private function grade(TryoutAnswer $answer): TryoutAssessmentData
    {
        $question = $answer->question;
        $part = $question->part === 2 ? 1 : 0;
        $strategy = $this->scoring->strategyFor($question->exam_type);
        $essay = trim((string) $answer->response);

        if ($essay === '' || (int) $answer->word_count === 0) {
            $criteria = array_map(
                fn (string $key): CriterionBandData => new CriterionBandData($key, WritingAssessmentAgent::CRITERIA[$key][$part], 0.0, 'Belum ada jawaban yang bisa dinilai.'),
                array_keys(WritingAssessmentAgent::CRITERIA),
            );

            return new TryoutAssessmentData($criteria, 0.0, 'Task ini belum kamu isi, jadi belum bisa dinilai.');
        }

        $agent = new WritingAssessmentAgent($question, $essay, (int) $answer->word_count);
        $started_at = microtime(true);

        try {
            AiConfiguration::ensureTextModelConfigured();

            $response = $agent->prompt($agent->buildPrompt());
        } catch (Throwable $exception) {
            $reason = AiFailureReason::fromThrowable($exception);

            Log::warning('tryout.writing.failed', [
                'tryout_answer_id' => $answer->id,
                'reason' => $reason->value,
                'exception' => $exception::class,
            ]);

            throw new TryoutAiException(TryoutAiException::messageFor($reason), 0, $exception, $reason);
        }

        $payload = $this->structuredPayload($response);
        $criteria = [];

        foreach (WritingAssessmentAgent::CRITERIA as $key => $labels) {
            $row = $payload[$key] ?? null;

            if (! is_array($row) || ! is_numeric($row['band'] ?? null)) {
                Log::warning('tryout.writing.invalid', ['tryout_answer_id' => $answer->id, 'missing' => $key]);

                throw new TryoutAiException(TryoutAiException::messageFor(AiFailureReason::InvalidResponse), reason: AiFailureReason::InvalidResponse);
            }

            $criteria[] = new CriterionBandData(
                key: $key,
                label: $labels[$part],
                band: $strategy->roundScore((float) $row['band']),
                reason: is_string($row['reason'] ?? null) ? trim($row['reason']) : '',
            );
        }

        Log::info('tryout.writing.completed', [
            'tryout_answer_id' => $answer->id,
            'duration_ms' => (int) round((microtime(true) - $started_at) * 1000),
        ]);

        return new TryoutAssessmentData(
            criteria: $criteria,
            band: $strategy->criteriaBand(array_map(fn (CriterionBandData $criterion): float => $criterion->band, $criteria)),
            summary: is_string($payload['summary'] ?? null) ? trim($payload['summary']) : '',
        );
    }

    /**
     * Store an outcome on the graded row unless it is already completed.
     *
     * Only the ai_* and band columns are written; the essay is never touched.
     *
     * @param  array<string, mixed>  $outcome
     */
    private function storeOutcome(TryoutAnswer $answer, array $outcome): void
    {
        DB::table('tryout_answers')
            ->where('id', $answer->id)
            ->where('ai_status', '!=', AiFeedbackStatus::Completed->value)
            ->update([...$outcome, 'updated_at' => now()]);
    }

    /**
     * Recompute the Writing band and the overall band after a task changed.
     */
    private function rescore(TryoutAnswer $answer): void
    {
        $attempt_section = $answer->attemptSection;

        $this->scoring->scoreProductive($attempt_section);
        $this->scoring->overall($attempt_section->attempt);
    }

    /**
     * Cache key of the per-answer AI lock.
     */
    private static function lockKey(TryoutAnswer $answer): string
    {
        return 'tryout-ai:answer:'.$answer->id;
    }
}
