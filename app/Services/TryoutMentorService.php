<?php

namespace App\Services;

use App\Ai\Agents\TryoutMentorAgent;
use App\Ai\AiConfiguration;
use App\Contracts\EntitlementServiceInterface;
use App\Contracts\TryoutMentorServiceInterface;
use App\Data\MentorReplyData;
use App\Enums\AiFailureReason;
use App\Exceptions\AiMentorException;
use App\Exceptions\TryoutReviewLockedException;
use App\Models\ModuleCoreSection;
use App\Models\TryoutAnswer;
use App\Models\TryoutAttempt;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Messages\AssistantMessage;
use Laravel\Ai\Messages\UserMessage;
use Throwable;

/**
 * Builds the trusted context of the Try Out mentor (the asked question with
 * its key and explanation, the mentee's answer, the theory module text) and
 * asks TryoutMentorAgent. Only questions answered in that attempt may be
 * asked about; the theory PDF is never read.
 */
class TryoutMentorService implements TryoutMentorServiceInterface
{
    /**
     * Maximum characters of a mentee question.
     */
    public const MAX_QUESTION_LENGTH = 1000;

    /**
     * Maximum previous chat turns forwarded to the model.
     */
    private const MAX_HISTORY_TURNS = 6;

    /**
     * Maximum characters forwarded per previous turn.
     */
    private const MAX_HISTORY_TURN_LENGTH = 3000;

    /**
     * @param  EntitlementServiceInterface  $entitlements  Plan rules (tryout_mentor).
     */
    public function __construct(
        private readonly EntitlementServiceInterface $entitlements,
    ) {}

    /**
     * Answer a question, optionally about one answered question of the attempt.
     *
     * @param  array<int, array<string, mixed>>  $history
     */
    public function ask(User $user, TryoutAttempt $attempt, string $question, ?string $question_code = null, array $history = []): MentorReplyData
    {
        if (! $this->entitlements->can($user, 'tryout_mentor')) {
            throw new TryoutReviewLockedException('Mentor AI Try Out tersedia untuk paket Pro.');
        }

        $question = trim($question);

        if ($question === '') {
            throw ValidationException::withMessages(['question' => 'Tulis pertanyaanmu dulu ya.']);
        }

        if (mb_strlen($question) > self::MAX_QUESTION_LENGTH) {
            throw ValidationException::withMessages(['question' => 'Pertanyaanmu terlalu panjang. Ringkas dulu ya.']);
        }

        $agent = new TryoutMentorAgent($this->context($attempt, $question_code));
        $messages = $this->historyMessages($history);

        try {
            AiConfiguration::ensureTextModelConfigured();

            $response = $messages === []
                ? $agent->prompt($agent->buildPrompt($question))
                : $agent->withMessages($messages)->prompt($agent->buildPrompt($question));
        } catch (Throwable $exception) {
            $reason = AiFailureReason::fromThrowable($exception);

            Log::warning('tryout.mentor.failed', [
                'tryout_attempt_id' => $attempt->id,
                'reason' => $reason->value,
                'exception' => $exception::class,
            ]);

            throw new AiMentorException(match ($reason) {
                AiFailureReason::Configuration => 'Layanan AI belum dikonfigurasi dengan lengkap. Silakan hubungi admin ya.',
                AiFailureReason::RateLimited => 'Mentor AI sedang menerima banyak pertanyaan. Tunggu sekitar satu menit, lalu coba lagi ya.',
                AiFailureReason::Timeout => 'Mentor AI butuh waktu lebih lama dari biasanya. Pertanyaanmu aman, coba kirim lagi ya.',
                AiFailureReason::Unavailable, AiFailureReason::InvalidResponse => 'Mentor AI sedang tidak bisa dihubungi. Coba lagi sebentar lagi ya.',
            }, 0, $exception);
        }

        $answer = trim((string) $response);

        if ($answer === '') {
            throw new AiMentorException('Mentor AI belum memberi jawaban. Pertanyaanmu aman, coba kirim lagi ya.');
        }

        return new MentorReplyData(answer: $answer);
    }

    /**
     * Trusted context: the asked question (key, explanation, mentee answer) and the theory text.
     *
     * @throws ValidationException When the code is not an answered question of the attempt.
     */
    private function context(TryoutAttempt $attempt, ?string $question_code): string
    {
        $attempt->loadMissing('tryout.theoryModule.coreSections');
        $lines = ['Try Out: '.$attempt->tryout->title];

        if (filled($question_code)) {
            /** @var TryoutAnswer|null $answer */
            $answer = $attempt->answers()
                ->whereNotNull('answered_at')
                ->whereHas('question', fn ($query) => $query->where('code', $question_code))
                ->with('question.unit')
                ->first();

            if ($answer === null) {
                throw ValidationException::withMessages(['question' => 'Soal ini belum kamu jawab di Try Out ini.']);
            }

            $question = $answer->question;
            $lines[] = '## Soal yang ditanyakan ('.$question->code.', '.$question->skill->getLabel().', level '.$question->level.')';

            if ($question->unit !== null) {
                $lines[] = 'Bagian: '.$question->unit->title;

                if (filled($question->unit->passage)) {
                    $lines[] = "Teks bacaan:\n".mb_substr((string) $question->unit->passage, 0, 4000);
                }

                if (filled($question->unit->transcript)) {
                    $lines[] = "Naskah audio:\n".mb_substr((string) $question->unit->transcript, 0, 4000);
                }
            }

            $lines[] = 'Pertanyaan: '.$question->prompt;

            foreach ($question->optionList() as $letter => $text) {
                $lines[] = $letter.'. '.$text;
            }

            $lines[] = 'Kunci jawaban: '.($question->answer_key ?? '-');

            if (filled($question->accepted_answers)) {
                $lines[] = 'Jawaban alternatif yang diterima: '.implode(', ', array_map('strval', (array) $question->accepted_answers));
            }

            $lines[] = 'Jawaban mentee: '.($answer->response ?? '(kosong)').($answer->is_correct ? ' (benar)' : ' (salah)');

            if (filled($question->explanation)) {
                $lines[] = 'Pembahasan resmi tim: '.$question->explanation;
            }
        }

        $theory = $attempt->tryout->theoryModule;

        if ($theory !== null && $theory->is_tryout_theory) {
            $text = $theory->coreSections
                ->map(fn (ModuleCoreSection $section): string => trim($section->subtitle."\n".strip_tags($section->body)))
                ->implode("\n\n");

            $text = trim(strip_tags((string) $theory->summary_material)."\n".$text);

            if ($text !== '') {
                $lines[] = "## Teori IELTS\n".mb_substr($text, 0, TryoutMentorAgent::MAX_THEORY_LENGTH);
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Convert the chat history into bounded SDK messages.
     *
     * @param  array<int, array<string, mixed>>  $history
     * @return array<int, UserMessage|AssistantMessage>
     */
    private function historyMessages(array $history): array
    {
        $messages = [];

        foreach (array_slice($history, -self::MAX_HISTORY_TURNS) as $turn) {
            $raw = $turn['content'] ?? '';
            $content = is_string($raw) ? mb_substr(trim($raw), 0, self::MAX_HISTORY_TURN_LENGTH) : '';

            if ($content === '') {
                continue;
            }

            $messages[] = ($turn['role'] ?? 'user') === 'assistant' ? new AssistantMessage($content) : new UserMessage($content);
        }

        return $messages;
    }
}
