<?php

namespace App\Services;

use App\Ai\Agents\ModuleMentorAgent;
use App\Ai\AiConfiguration;
use App\Contracts\ModuleMentorServiceInterface;
use App\Data\MentorReplyData;
use App\Exceptions\AiConfigurationException;
use App\Exceptions\AiMentorException;
use App\Models\Module;
use App\Models\PracticeForm;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Messages\AssistantMessage;
use Laravel\Ai\Messages\UserMessage;
use Throwable;

class ModuleMentorService implements ModuleMentorServiceInterface
{
    /**
     * Maximum number of characters accepted from a mentee question.
     */
    public const MAX_QUESTION_LENGTH = 1000;

    /**
     * Maximum number of previous chat turns forwarded to the model.
     */
    public const MAX_HISTORY_TURNS = 6;

    /**
     * Answer a mentee question using the module context and bounded chat history.
     *
     * @param  array<int, array<string, mixed>>  $history
     *
     * @throws ValidationException
     * @throws AiMentorException
     */
    public function reply(
        Module $module,
        ?PracticeForm $practice_form,
        string $question,
        array $history = [],
        ?string $active_field = null,
    ): MentorReplyData {
        $question = trim($question);

        if ($question === '') {
            throw ValidationException::withMessages([
                'question' => 'Tulis pertanyaanmu dulu ya.',
            ]);
        }

        if (mb_strlen($question) > self::MAX_QUESTION_LENGTH) {
            throw ValidationException::withMessages([
                'question' => 'Pertanyaanmu terlalu panjang. Ringkas dulu agar AI Mentor bisa menjawab lebih fokus.',
            ]);
        }

        $agent = new ModuleMentorAgent($module, $practice_form, $active_field);

        $messages = $this->historyMessages($history);
        $started_at = microtime(true);

        try {
            AiConfiguration::ensureTextModelConfigured();

            $response = $messages === []
                ? $agent->prompt($agent->buildPrompt($question))
                : $agent->withMessages($messages)->prompt($agent->buildPrompt($question));
        } catch (Throwable $exception) {
            Log::warning('AI Mentor request failed', [
                'module_id' => $module->id,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            if ($exception instanceof AiConfigurationException) {
                throw new AiMentorException('Layanan AI belum dikonfigurasi dengan lengkap. Silakan hubungi admin ya.', 0, $exception);
            }

            $timedOut = $exception->getCode() === 28
                || str_contains(strtolower($exception->getMessage()), 'timed out')
                || str_contains(strtolower($exception->getMessage()), 'timeout');

            throw new AiMentorException(
                $timedOut
                    ? 'AI Mentor butuh waktu lebih lama dari biasanya. Pertanyaanmu aman, coba kirim lagi ya.'
                    : 'AI Mentor sedang tidak bisa dihubungi. Coba lagi sebentar lagi ya.',
                0,
                $exception,
            );
        }

        $answer = trim((string) $response);

        if ($answer === '') {
            throw new AiMentorException('AI Mentor tidak mengembalikan jawaban yang valid.');
        }

        Log::debug('ai.request', [
            'feature' => 'mentor',
            'module_id' => $module->id,
            'practice_form_id' => $practice_form?->id,
            'provider' => AiConfiguration::provider(),
            'model' => AiConfiguration::textModel(),
            'duration_ms' => (int) round((microtime(true) - $started_at) * 1000),
        ]);

        return new MentorReplyData(answer: $answer);
    }

    /**
     * Convert the stored chat history into bounded SDK messages.
     *
     * @param  array<int, array<string, mixed>>  $history
     * @return array<int, UserMessage|AssistantMessage>
     */
    private function historyMessages(array $history): array
    {
        $recent = array_slice($history, -self::MAX_HISTORY_TURNS);

        $messages = [];

        foreach ($recent as $turn) {
            $content = trim((string) ($turn['content'] ?? ''));

            if ($content === '') {
                continue;
            }

            $messages[] = ($turn['role'] ?? 'user') === 'assistant'
                ? new AssistantMessage($content)
                : new UserMessage($content);
        }

        return $messages;
    }
}
