<?php

namespace App\Services;

use App\Ai\Agents\SpeakingAssessmentAgent;
use App\Ai\AiConfiguration;
use App\Concerns\ExtractsStructuredResponse;
use App\Contracts\SpeakingAssessmentServiceInterface;
use App\Contracts\TryoutScoringServiceInterface;
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
 * Grades the Speaking subtest of the Try Out. The parts are transcribed in the
 * mentee's browser (Web Speech API, no audio reaches the server), so one
 * synchronous call to SpeakingAssessmentAgent grades the three transcripts
 * together, under a cache lock; a failure fails only the assessment and
 * "Nilai ulang" retries it.
 */
class SpeakingAssessmentService implements SpeakingAssessmentServiceInterface
{
    use ExtractsStructuredResponse;

    /**
     * @param  TryoutScoringServiceInterface  $scoring  Speaking and overall band.
     */
    public function __construct(
        private readonly TryoutScoringServiceInterface $scoring,
    ) {}

    /**
     * Grade the Speaking subtest from the transcripts of its parts.
     *
     * Parts without a transcript count as empty answers. When no part was
     * answered at all, every criterion is 0 and the model is not called.
     */
    public function assess(TryoutAttemptSection $attempt_section): TryoutAttemptSection
    {
        $lock = Cache::lock('tryout-ai:section:'.$attempt_section->id, TryoutAiBudget::LOCK_SECONDS);

        if (! $lock->get()) {
            return $attempt_section;
        }

        try {
            $attempt_section->refresh();

            if ($attempt_section->ai_status === AiFeedbackStatus::Completed) {
                return $attempt_section;
            }

            TryoutAiBudget::ensureExecutionTime();

            $attempt_section->forceFill([
                'ai_status' => AiFeedbackStatus::Processing,
                'ai_error' => null,
                'ai_attempts' => $attempt_section->ai_attempts + 1,
                'ai_heartbeat_at' => now(),
            ])->save();

            $assessment = $this->grade($attempt_section);

            DB::table('tryout_attempt_sections')
                ->where('id', $attempt_section->id)
                ->where('ai_status', '!=', AiFeedbackStatus::Completed->value)
                ->update([
                    'ai_status' => AiFeedbackStatus::Completed->value,
                    'ai_assessment' => json_encode($assessment->toArray()),
                    'ai_error' => null,
                    'updated_at' => now(),
                ]);
        } catch (Throwable $exception) {
            report($exception);

            DB::table('tryout_attempt_sections')
                ->where('id', $attempt_section->id)
                ->where('ai_status', '!=', AiFeedbackStatus::Completed->value)
                ->update([
                    'ai_status' => AiFeedbackStatus::Failed->value,
                    'ai_error' => $exception instanceof TryoutAiException ? $exception->getMessage() : TryoutAiException::messageFor(AiFailureReason::Unavailable),
                    'updated_at' => now(),
                ]);
        } finally {
            $lock->release();
        }

        $attempt_section->refresh();
        $this->scoring->scoreProductive($attempt_section);
        $this->scoring->overall($attempt_section->attempt);

        return $attempt_section->refresh();
    }

    /**
     * Grade a closed Speaking subtest that still waits for its assessment.
     */
    public function nextStep(TryoutAttemptSection $attempt_section): bool
    {
        if ($attempt_section->status !== TryoutSectionStatus::Submitted || $attempt_section->ai_status !== AiFeedbackStatus::Pending) {
            return false;
        }

        $this->assess($attempt_section);

        return true;
    }

    /**
     * Grade a failed or stalled Speaking assessment again ("Nilai ulang").
     */
    public function retry(TryoutAttemptSection $attempt_section): TryoutAttemptSection
    {
        $failed = $attempt_section->ai_status === AiFeedbackStatus::Failed || $attempt_section->isStalled();

        if ($failed && $attempt_section->ai_attempts <= (int) config('tryout.ai.max_retries_per_answer', 2)) {
            return $this->assess($attempt_section);
        }

        return $attempt_section;
    }

    /**
     * Call the model (or score an empty subtest 0) and read the four criteria.
     *
     * @throws TryoutAiException
     */
    private function grade(TryoutAttemptSection $attempt_section): TryoutAssessmentData
    {
        $answers = $attempt_section->answers()->with('question')->get();
        $strategy = $this->scoring->strategyFor($attempt_section->section->tryout->exam_type);

        $parts = $answers->map(fn (TryoutAnswer $answer): array => [
            'part' => $answer->question->part ?? $answer->sequence,
            'prompt' => $answer->question->prompt,
            'items' => array_values(array_filter($answer->question->prompt_items ?? [], 'is_string')),
            'transcript' => (string) $answer->transcript,
            'seconds' => $answer->question->speak_seconds,
        ])->values()->all();

        if (collect($parts)->every(fn (array $part): bool => trim($part['transcript']) === '')) {
            $criteria = array_map(
                fn (string $key): CriterionBandData => new CriterionBandData($key, SpeakingAssessmentAgent::CRITERIA[$key], 0.0, 'Belum ada jawaban yang bisa dinilai.'),
                array_keys(SpeakingAssessmentAgent::CRITERIA),
            );

            return new TryoutAssessmentData($criteria, 0.0, 'Belum ada jawaban Speaking yang terkirim.', true);
        }

        $agent = new SpeakingAssessmentAgent($parts);

        try {
            AiConfiguration::ensureTextModelConfigured();

            $response = $agent->prompt($agent->buildPrompt());
        } catch (Throwable $exception) {
            $reason = AiFailureReason::fromThrowable($exception);

            Log::warning('tryout.speaking.failed', [
                'tryout_attempt_section_id' => $attempt_section->id,
                'reason' => $reason->value,
                'exception' => $exception::class,
            ]);

            throw new TryoutAiException(TryoutAiException::messageFor($reason), 0, $exception, $reason);
        }

        $payload = $this->structuredPayload($response);
        $criteria = [];

        foreach (SpeakingAssessmentAgent::CRITERIA as $key => $label) {
            $row = $payload[$key] ?? null;

            if (! is_array($row) || ! is_numeric($row['band'] ?? null)) {
                throw new TryoutAiException(TryoutAiException::messageFor(AiFailureReason::InvalidResponse), reason: AiFailureReason::InvalidResponse);
            }

            $criteria[] = new CriterionBandData(
                key: $key,
                label: $label,
                band: $strategy->roundScore((float) $row['band']),
                reason: is_string($row['reason'] ?? null) ? trim($row['reason']) : '',
            );
        }

        return new TryoutAssessmentData(
            criteria: $criteria,
            band: $strategy->criteriaBand(array_map(fn (CriterionBandData $criterion): float => $criterion->band, $criteria)),
            summary: is_string($payload['summary'] ?? null) ? trim($payload['summary']) : '',
            pronunciation_is_estimate: true,
        );
    }
}
