<?php

namespace App\Services;

use App\Ai\Agents\PracticeFeedbackAgent;
use App\Ai\AiConfiguration;
use App\Concerns\ExtractsStructuredResponse;
use App\Contracts\AiFeedbackServiceInterface;
use App\Data\AiFeedbackData;
use App\Exceptions\AiConfigurationException;
use App\Exceptions\AiFeedbackException;
use App\Models\Module;
use App\Models\PracticeForm;
use Illuminate\Support\Facades\Log;
use Throwable;

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
            Log::warning('AI Feedback request failed', [
                'module_id' => $module->id,
                'practice_form_id' => $practice_form->id,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            if ($exception instanceof AiConfigurationException) {
                throw new AiFeedbackException('Layanan AI belum dikonfigurasi dengan lengkap. Silakan hubungi admin ya.', 0, $exception);
            }

            $timedOut = $exception->getCode() === 28
                || str_contains(strtolower($exception->getMessage()), 'timed out')
                || str_contains(strtolower($exception->getMessage()), 'timeout');

            throw new AiFeedbackException(
                $timedOut
                    ? 'Penilaian AI butuh waktu lebih lama dari biasanya. Jawabanmu tetap tersimpan, coba lagi ya.'
                    : 'Layanan AI tidak dapat dihubungi. Jawabanmu tetap tersimpan, coba lagi nanti.',
                0,
                $exception,
            );
        }

        $payload = $this->structuredPayload($response);
        $feedback = (string) ($payload['feedback'] ?? '');

        if (trim($feedback) === '') {
            throw new AiFeedbackException('AI tidak mengembalikan feedback yang valid.');
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
            strengths: $this->stringList($payload['strengths'] ?? []),
            improvements: $this->stringList($payload['improvements'] ?? []),
            feedback: $feedback,
            next_steps: $this->stringList($payload['next_steps'] ?? []),
        );
    }

    /**
     * Normalise an arbitrary AI value into a list of non-empty strings.
     *
     * @return array<int, string>
     */
    private function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $items = [];

        foreach ($value as $item) {
            if (is_scalar($item) && trim((string) $item) !== '') {
                $items[] = trim((string) $item);
            }
        }

        return $items;
    }
}
