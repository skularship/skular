<?php

namespace App\Services;

use App\Ai\Agents\PracticeFeedbackAgent;
use App\Ai\AiConfiguration;
use App\Concerns\ExtractsStructuredResponse;
use App\Contracts\AiFeedbackServiceInterface;
use App\Data\AiFeedbackData;
use App\Data\FeedbackPointData;
use App\Enums\AiFailureReason;
use App\Exceptions\AiFeedbackException;
use App\Models\Module;
use App\Models\PracticeForm;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Grades one practice submission with PracticeFeedbackAgent.
 *
 * Provider errors are classified with AiFailureReason, logged, and rethrown as
 * AiFeedbackException with a friendly Indonesian message.
 */
class AiFeedbackService implements AiFeedbackServiceInterface
{
    use ExtractsStructuredResponse;

    /**
     * Generate structured learning feedback for a practice submission.
     *
     * @param  array<string, mixed>  $answers
     *
     * @throws AiFeedbackException
     */
    public function generate(Module $module, PracticeForm $practice_form, array $answers): AiFeedbackData
    {
        $agent = new PracticeFeedbackAgent($module, $practice_form, $answers);
        $prompt = $agent->buildPrompt();
        $started_at = microtime(true);

        try {
            AiConfiguration::ensureTextModelConfigured();

            $response = $agent->prompt($prompt);
        } catch (Throwable $exception) {
            $reason = AiFailureReason::fromThrowable($exception);

            $this->logAttempt($module, $practice_form, $prompt, $started_at, 'failed', $reason, $exception);

            Log::warning('AI Feedback request failed', [
                'module_id' => $module->id,
                'practice_form_id' => $practice_form->id,
                'reason' => $reason->value,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            throw new AiFeedbackException(self::messageFor($reason), 0, $exception, $reason);
        }

        $payload = $this->structuredPayload($response);
        $score = $payload['score'] ?? null;
        $feedback = is_scalar($payload['feedback'] ?? null) ? trim((string) $payload['feedback']) : '';
        $strengths = FeedbackPointData::listFrom($payload['strengths'] ?? null);
        $improvements = FeedbackPointData::listFrom($payload['improvements'] ?? null);
        $next_steps = FeedbackPointData::listFrom($payload['next_steps'] ?? null);

        // A partial answer is still useful to the mentee: a missing summary or
        // missing points are left out of the page. Only an answer without any
        // text, or without a score to derive the label from, is rejected.
        $has_text = $feedback !== '' || $strengths !== [] || $improvements !== [] || $next_steps !== [];

        if (! is_numeric($score) || ! $has_text) {
            $this->logAttempt($module, $practice_form, $prompt, $started_at, 'invalid', AiFailureReason::InvalidResponse);

            Log::warning('AI Feedback returned an unusable evaluation', [
                'module_id' => $module->id,
                'practice_form_id' => $practice_form->id,
                'has_score' => is_numeric($score),
                'has_text' => $has_text,
            ]);

            throw new AiFeedbackException(self::messageFor(AiFailureReason::InvalidResponse), reason: AiFailureReason::InvalidResponse);
        }

        $this->logAttempt($module, $practice_form, $prompt, $started_at, 'completed');

        return new AiFeedbackData(
            score: (int) round((float) $score),
            // Malformed items are dropped instead of failing the evaluation;
            // a model that still answers with plain strings is accepted too.
            strengths: $strengths,
            improvements: $improvements,
            feedback: $feedback,
            next_steps: $next_steps,
        );
    }

    /**
     * Get the friendly Indonesian message shown to the mentee for a failure.
     *
     * Every message says the answer is safe and what to do next.
     */
    public static function messageFor(AiFailureReason $reason): string
    {
        return match ($reason) {
            AiFailureReason::Configuration => 'Layanan AI belum dikonfigurasi dengan lengkap. Jawabanmu tetap tersimpan, silakan hubungi admin ya.',
            AiFailureReason::RateLimited => 'Penilaian AI sedang ramai. Jawabanmu tetap tersimpan, coba nilai ulang sekitar satu menit lagi ya.',
            AiFailureReason::Timeout => 'Penilaian AI butuh waktu lebih lama dari biasanya. Jawabanmu tetap tersimpan, coba nilai ulang ya.',
            AiFailureReason::Unavailable => 'Layanan AI tidak dapat dihubungi. Jawabanmu tetap tersimpan, coba nilai ulang beberapa saat lagi.',
            AiFailureReason::InvalidResponse => 'AI belum memberi penilaian yang lengkap. Jawabanmu tetap tersimpan, coba nilai ulang ya.',
        };
    }

    /**
     * Write one structured `ai.feedback.attempt` line per provider call.
     *
     * Carries only metadata (sizes, timings, classification), never the
     * mentee answer or the prompt text. The response id, attempt number and
     * trigger come from the Context scope opened by PracticeSubmissionService.
     *
     * @param  'completed'|'invalid'|'failed'  $outcome  'invalid' = the provider answered without a usable evaluation.
     */
    private function logAttempt(
        Module $module,
        PracticeForm $practice_form,
        string $prompt,
        float $started_at,
        string $outcome,
        ?AiFailureReason $reason = null,
        ?Throwable $exception = null,
    ): void {
        Log::info('ai.feedback.attempt', [
            ...Context::only(['ai_response_id', 'ai_attempt', 'ai_trigger']),
            'module_id' => $module->id,
            'practice_form_id' => $practice_form->id,
            'model' => config('ai.providers.'.config('ai.default').'.models.text.default'),
            'outcome' => $outcome,
            'reason' => $reason?->value,
            'exception' => $exception === null ? null : $exception::class,
            'previous_exception' => $exception?->getPrevious() === null ? null : $exception->getPrevious()::class,
            'duration_ms' => (int) round((microtime(true) - $started_at) * 1000),
            'prompt_chars' => mb_strlen($prompt),
            'invalid_response' => $outcome === 'invalid',
        ]);
    }
}
