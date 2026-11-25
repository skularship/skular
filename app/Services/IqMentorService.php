<?php

namespace App\Services;

use App\Ai\Agents\IqMentorAgent;
use App\Ai\AiConfiguration;
use App\Contracts\IqMentorServiceInterface;
use App\Contracts\IqResultServiceInterface;
use App\Data\MentorReplyData;
use App\Enums\AiFailureReason;
use App\Enums\IqSubtest;
use App\Exceptions\AiMentorException;
use App\Exceptions\IqLockedException;
use App\Models\IqAnswer;
use App\Models\IqAttempt;
use App\Models\IqQuestion;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Messages\AssistantMessage;
use Laravel\Ai\Messages\UserMessage;
use Throwable;

/**
 * Builds the trusted context of the Tes IQ mentor (the asked question with
 * its key, explanation and the mentee's choice, the score summary and the
 * internal material notes of the test) and asks IqMentorAgent. Only
 * questions of that attempt may be asked about; the material PDF itself is
 * never read.
 */
class IqMentorService implements IqMentorServiceInterface
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
     * @param  IqResultServiceInterface  $results  Paid access check.
     */
    public function __construct(
        private readonly IqResultServiceInterface $results,
    ) {}

    /**
     * Answer a question, optionally about one question of the attempt.
     *
     * @param  array<int, array<string, mixed>>  $history
     */
    public function ask(User $user, IqAttempt $attempt, string $question, ?int $position = null, array $history = []): MentorReplyData
    {
        if (! $this->results->canSeeResults($user)) {
            throw new IqLockedException('Mentor AI Tes IQ terbuka setelah kamu membuka akses.');
        }

        $question = trim($question);

        if ($question === '') {
            throw ValidationException::withMessages(['question' => 'Tulis pertanyaanmu dulu ya.']);
        }

        if (mb_strlen($question) > self::MAX_QUESTION_LENGTH) {
            throw ValidationException::withMessages(['question' => 'Pertanyaanmu terlalu panjang. Ringkas dulu ya.']);
        }

        $agent = new IqMentorAgent($this->context($attempt, $position));
        $messages = $this->historyMessages($history);

        try {
            AiConfiguration::ensureTextModelConfigured();

            $response = $messages === []
                ? $agent->prompt($agent->buildPrompt($question))
                : $agent->withMessages($messages)->prompt($agent->buildPrompt($question));
        } catch (Throwable $exception) {
            $reason = AiFailureReason::fromThrowable($exception);

            Log::warning('iq.mentor.failed', [
                'iq_attempt_id' => $attempt->id,
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
     * Trusted context: test, score summary, the asked question and the material notes.
     *
     * @throws ValidationException When the position is not a question of the attempt.
     */
    private function context(IqAttempt $attempt, ?int $position): string
    {
        $attempt->loadMissing('test');
        $lines = [
            'Tes: '.$attempt->test->title.' ('.$attempt->total_questions.' soal, '.$attempt->test->duration_minutes.' menit).',
            'Hasil mentee: '.($attempt->correct_count ?? 0).' dari '.$attempt->total_questions.' benar ('.($attempt->score_percent ?? 0).'%), kategori latihan "'.($attempt->category ?? '-').'". Ini skor latihan, bukan skor IQ.',
        ];

        foreach ($attempt->subtest_scores ?? [] as $subtest => $score) {
            $label = IqSubtest::tryFrom((string) $subtest)?->getLabel() ?? (string) $subtest;
            $lines[] = '- '.$label.': '.$score['correct'].'/'.$score['total'];
        }

        if ($position !== null) {
            $id = $attempt->question_ids[$position - 1] ?? null;
            $question = $id === null ? null : IqQuestion::query()->find((int) $id);

            if (! $question instanceof IqQuestion) {
                throw ValidationException::withMessages(['question' => 'Soal ini tidak ada di tesmu.']);
            }

            /** @var IqAnswer|null $answer */
            $answer = $attempt->answers()->where('iq_question_id', $question->id)->first();

            $lines[] = '## Soal yang ditanyakan (nomor '.$position.', subtes '.$question->subtest->getLabel().')';

            if (filled($question->passage)) {
                $lines[] = "Bacaan:\n".mb_substr((string) $question->passage, 0, 3000);
            }

            if (filled($question->image_alt)) {
                $lines[] = 'Gambar soal (deskripsi): '.$question->image_alt;
            }

            $lines[] = 'Pertanyaan: '.$question->prompt;

            foreach ($question->optionList() as $letter => $text) {
                $lines[] = $letter.'. '.$text;
            }

            $lines[] = 'Kunci jawaban: '.($question->answer_key ?? '-');
            $lines[] = 'Jawaban mentee: '.($answer === null || $answer->choice === null
                ? '(tidak dijawab)'
                : $answer->choice.($answer->is_correct ? ' (benar)' : ' (salah)'));

            if (filled($question->explanation)) {
                $lines[] = 'Pembahasan tim: '.$question->explanation;
            }
        }

        $notes = trim(strip_tags((string) $attempt->test->mentor_notes));

        if ($notes !== '') {
            $lines[] = "## Materi internal Skular\n".mb_substr($notes, 0, IqMentorAgent::MAX_NOTES_LENGTH);
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
