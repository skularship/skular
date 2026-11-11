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
        $started_at = microtime(true);

        try {
            AiConfiguration::ensureTextModelConfigured();

            $response = $agent->prompt($agent->buildPrompt());
        } catch (Throwable $exception) {
            $reason = AiFailureReason::fromThrowable($exception);

            Log::warning('AI Feedback request failed', [
                'module_id' => $module->id,
                'practice_form_id' => $practice_form->id,
                'reason' => $reason->value,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            throw new AiFeedbackException(match ($reason) {
                AiFailureReason::Configuration => 'Layanan AI belum dikonfigurasi dengan lengkap. Jawabanmu tetap tersimpan, silakan hubungi admin ya.',
                AiFailureReason::RateLimited => 'Penilaian AI sedang ramai. Jawabanmu tetap tersimpan, coba nilai ulang sekitar satu menit lagi ya.',
                AiFailureReason::Timeout => 'Penilaian AI butuh waktu lebih lama dari biasanya. Jawabanmu tetap tersimpan, coba lagi ya.',
                AiFailureReason::Unavailable => 'Layanan AI tidak dapat dihubungi. Jawabanmu tetap tersimpan, coba lagi nanti.',
            }, 0, $exception, $reason);
        }

        $payload = $this->structuredPayload($response);
        $feedback = (string) ($payload['feedback'] ?? '');

        if (trim($feedback) === '') {
            Log::warning('AI Feedback returned an empty evaluation', [
                'module_id' => $module->id,
                'practice_form_id' => $practice_form->id,
            ]);

            throw new AiFeedbackException('AI belum memberi penilaian yang lengkap. Jawabanmu tetap tersimpan, coba nilai ulang ya.');
        }

        Log::debug('ai.request', [
            'feature' => 'feedback',
            'module_id' => $module->id,
            'practice_form_id' => $practice_form->id,
            'provider' => AiConfiguration::provider(),
            'model' => AiConfiguration::textModel(),
            'duration_ms' => (int) round((microtime(true) - $started_at) * 1000),
        ]);

        $score = $payload['score'] ?? null;

        return new AiFeedbackData(
            score: is_numeric($score) ? (int) $score : 0,
            // Malformed items are dropped instead of failing the evaluation;
            // a model that still answers with plain strings is accepted too.
            strengths: FeedbackPointData::listFrom($payload['strengths'] ?? null),
            improvements: FeedbackPointData::listFrom($payload['improvements'] ?? null),
            feedback: trim($feedback),
            next_steps: FeedbackPointData::listFrom($payload['next_steps'] ?? null),
        );
    }
}
