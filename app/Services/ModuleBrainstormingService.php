<?php

namespace App\Services;

use App\Ai\Agents\ModuleBrainstormingAgent;
use App\Ai\AiConfiguration;
use App\Concerns\ExtractsStructuredResponse;
use App\Contracts\ModuleBrainstormingServiceInterface;
use App\Data\BrainstormOutlineData;
use App\Data\BrainstormOutlineSectionData;
use App\Data\BrainstormResultData;
use App\Exceptions\AiBrainstormingException;
use App\Exceptions\AiConfigurationException;
use App\Models\Module;
use App\Models\PracticeForm;
use Illuminate\Support\Facades\Log;
use Throwable;

class ModuleBrainstormingService implements ModuleBrainstormingServiceInterface
{
    use ExtractsStructuredResponse;

    /**
     * Maximum number of characters accepted for an outline title.
     */
    private const MAX_TITLE_LENGTH = 160;

    /**
     * Maximum number of characters accepted for the core idea.
     */
    private const MAX_CORE_IDEA_LENGTH = 1200;

    /**
     * Maximum number of characters accepted for an outline bullet.
     */
    private const MAX_BULLET_LENGTH = 500;

    public function __construct(
        private readonly BrainstormingConfigService $config,
    ) {}

    /**
     * Get the questions configured by the curriculum team.
     *
     * This step only reads the database. It never calls the AI provider, so
     * brainstorming stays usable when the provider is unavailable.
     */
    public function questions(Module $module, PracticeForm $practice_form): BrainstormResultData
    {
        return new BrainstormResultData(questions: $this->config->questions($module));
    }

    /**
     * Build the draft outline from the mentee's validated answers.
     *
     * @param  array<string, mixed>  $answers
     *
     * @throws AiBrainstormingException
     */
    public function summary(Module $module, PracticeForm $practice_form, array $answers): BrainstormResultData
    {
        $resolution = $this->config->resolveAnswers($module, $answers);

        if ($resolution['errors'] !== []) {
            throw new AiBrainstormingException((string) reset($resolution['errors']));
        }

        if ($resolution['answers'] === []) {
            throw new AiBrainstormingException('Jawab minimal satu pertanyaan dulu sebelum meminta kerangka draf.');
        }

        $payload = $this->run(
            new ModuleBrainstormingAgent($module, $practice_form, $resolution['answers']),
            $module,
            $practice_form,
        );

        $outline = $this->parseOutline($payload);

        if (! $outline instanceof BrainstormOutlineData) {
            throw new AiBrainstormingException('AI belum bisa menyusun kerangka yang valid. Coba lagi ya.');
        }

        return new BrainstormResultData(
            questions: [],
            summary: $this->outlineText($outline),
            outline: $outline,
        );
    }

    /**
     * Run the agent and return the decoded structured payload.
     *
     * Only diagnostics are logged: no prompt, answers, or provider key leave
     * the server. Provider errors become a friendly, retryable message instead
     * of surfacing raw exception details to the mentee.
     *
     * @return array<string, mixed>
     *
     * @throws AiBrainstormingException
     */
    private function run(
        ModuleBrainstormingAgent $agent,
        Module $module,
        PracticeForm $practice_form,
    ): array {
        $started_at = microtime(true);

        try {
            AiConfiguration::ensureTextModelConfigured();

            $response = $agent->prompt($agent->buildPrompt());
        } catch (Throwable $exception) {
            Log::warning('AI Brainstorming request failed', [
                'operation' => 'outline',
                'module_id' => $module->id,
                'practice_form_id' => $practice_form->id,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            throw new AiBrainstormingException($this->failureMessage($exception), 0, $exception);
        }

        $payload = $this->structuredPayload($response);

        Log::debug('ai.request', [
            'feature' => 'brainstorming',
            'operation' => 'outline',
            'module_id' => $module->id,
            'practice_form_id' => $practice_form->id,
            'provider' => AiConfiguration::provider(),
            'model' => AiConfiguration::textModel(),
            'duration_ms' => (int) round((microtime(true) - $started_at) * 1000),
        ]);

        return $payload;
    }

    /**
     * Convert a provider failure into safe guidance for the mentee.
     */
    private function failureMessage(Throwable $exception): string
    {
        if ($exception instanceof AiConfigurationException) {
            return 'Layanan AI belum dikonfigurasi dengan lengkap. Silakan hubungi admin ya.';
        }

        $message = strtolower($exception->getMessage());

        $timedOut = $exception->getCode() === 28
            || str_contains($message, 'timed out')
            || str_contains($message, 'timeout');

        return $timedOut
            ? 'AI butuh waktu lebih lama dari biasanya. Jawaban brainstormingmu tetap aman, coba lagi ya.'
            : 'AI belum berhasil menyusun kerangka. Jawaban brainstormingmu tetap aman, coba lagi ya.';
    }

    /**
     * Validate and normalise the structured outline returned by the model.
     *
     * @param  array<string, mixed>  $payload
     */
    private function parseOutline(array $payload): ?BrainstormOutlineData
    {
        $title = $this->cleanText($payload['title'] ?? null, self::MAX_TITLE_LENGTH);
        $core_idea = $this->cleanText($payload['core_idea'] ?? null, self::MAX_CORE_IDEA_LENGTH);

        if ($title === null || $core_idea === null) {
            return null;
        }

        $sections = [];

        if (is_array($payload['sections'] ?? null)) {
            foreach ($payload['sections'] as $section) {
                if (count($sections) >= 6) {
                    break;
                }

                if (! is_array($section)) {
                    continue;
                }

                $field = $this->cleanText($section['field'] ?? null, 200);
                $guidance = $this->cleanText($section['guidance'] ?? null, self::MAX_BULLET_LENGTH);

                if ($field === null || $guidance === null) {
                    continue;
                }

                $sections[] = new BrainstormOutlineSectionData(
                    field: $field,
                    guidance: $guidance,
                    points: $this->stringList($section['points'] ?? null, 5),
                );
            }
        }

        return new BrainstormOutlineData(
            title: $title,
            core_idea: $core_idea,
            points: $this->stringList($payload['points'] ?? null, 6),
            writing_flow: $this->stringList($payload['writing_flow'] ?? null, 6),
            missing_information: $this->stringList($payload['missing_information'] ?? null, 6),
            reflection_questions: $this->stringList($payload['reflection_questions'] ?? null, 5),
            sections: $sections,
        );
    }

    /**
     * Turn the structured outline into plain text that can be copied.
     */
    private function outlineText(BrainstormOutlineData $outline): string
    {
        $lines = [
            $outline->title,
            '',
            'Inti gagasan: '.$outline->core_idea,
        ];

        $lines = [...$lines, ...$this->labelledList('Poin yang dapat dikembangkan', $outline->points)];
        $lines = [...$lines, ...$this->labelledList('Alur penulisan', $outline->writing_flow)];
        $lines = [...$lines, ...$this->labelledList('Informasi yang perlu dilengkapi', $outline->missing_information)];
        $lines = [...$lines, ...$this->labelledList('Pertanyaan refleksi lanjutan', $outline->reflection_questions)];

        if ($outline->sections !== []) {
            $lines[] = '';
            $lines[] = 'Panduan per bagian:';

            foreach ($outline->sections as $section) {
                $lines[] = '- '.$section->field.': '.$section->guidance;

                foreach ($section->points as $point) {
                    $lines[] = '  - '.$point;
                }
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Render a titled bullet list, omitting empty sections.
     *
     * @param  array<int, string>  $items
     * @return array<int, string>
     */
    private function labelledList(string $title, array $items): array
    {
        if ($items === []) {
            return [];
        }

        return [
            '',
            $title.':',
            ...array_map(fn (string $item): string => '- '.$item, $items),
        ];
    }

    /**
     * Clean and bound a list of model-provided strings.
     *
     * @return array<int, string>
     */
    private function stringList(mixed $raw, int $max): array
    {
        if (! is_array($raw)) {
            return [];
        }

        $items = [];

        foreach ($raw as $item) {
            $clean = $this->cleanText($item, self::MAX_BULLET_LENGTH);

            if ($clean === null) {
                continue;
            }

            $items[] = $clean;

            if (count($items) >= $max) {
                break;
            }
        }

        return $items;
    }

    /**
     * Trim a value into a bounded string, or null when it is unusable.
     */
    private function cleanText(mixed $value, int $limit): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $text = trim((string) $value);

        if ($text === '') {
            return null;
        }

        return mb_substr($text, 0, $limit);
    }
}
