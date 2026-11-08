<?php

namespace App\Livewire\Learning;

use App\Contracts\MentorQuotaServiceInterface;
use App\Contracts\ModuleMentorServiceInterface;
use App\Contracts\ProgressServiceInterface;
use App\Enums\CheckpointStatus;
use App\Exceptions\AiMentorException;
use App\Models\Module;
use App\Models\PracticeForm;
use App\Models\User;
use App\Services\ModuleMentorService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * A lightweight AI Mentor panel anchored to the module practice form.
 *
 * The chat transcript is intentionally ephemeral: history lives in component
 * state and is never persisted, so nothing leaks between mentees. Only the
 * number of questions sent to the model is stored, per mentee per module, so
 * the quota cannot be bypassed by refreshing the page.
 *
 * @property-read int $remaining
 * @property-read int $limit
 */
class MentorChat extends Component
{
    /**
     * Maximum AI Mentor requests a single mentee may make per minute.
     */
    private const RATE_LIMIT_PER_MINUTE = 12;

    /**
     * Maximum chat bubbles kept in component state.
     */
    private const MAX_TRANSCRIPT = 40;

    #[Locked]
    public Module $module;

    /**
     * The practice form that anchors the conversation, when one is active.
     */
    public ?int $form_id = null;

    /**
     * Label of the practice field the mentee is currently filling.
     */
    public ?string $field_label = null;

    public bool $open = false;

    public string $question = '';

    /**
     * Chat transcript kept in component state only.
     *
     * @var array<int, array{role: string, content: string, error?: bool, source?: string}>
     */
    public array $messages = [];

    /**
     * The last question that failed, so the mentee can retry without retyping.
     */
    public ?string $failed_question = null;

    /**
     * Suggested questions shown as clickable chips.
     *
     * Admin-written suggestions come first. A module without them falls back
     * to a few questions built from its own content; those have no stored
     * answer, so they are sent to the AI Mentor and use the quota.
     *
     * @return array<int, array{question: string, answer: string|null}>
     */
    public function suggestions(): array
    {
        $suggestions = $this->module->mentorSuggestionList();

        if ($suggestions !== []) {
            return $suggestions;
        }

        $form = $this->practiceForm();

        $fallback = [
            'Apa poin terpenting dari materi "'.$this->module->title.'"?',
            $form instanceof PracticeForm
                ? 'Bagaimana cara memulai latihan "'.$form->title.'"?'
                : 'Bagaimana cara menerapkan materi ini?',
            'Kesalahan apa yang sering terjadi di bagian ini?',
        ];

        return array_map(
            fn (string $question): array => ['question' => $question, 'answer' => null],
            $fallback,
        );
    }

    /**
     * How many questions the mentee may still send to the model for this module.
     */
    #[Computed]
    public function remaining(): int
    {
        return $this->quota()->remaining($this->user(), $this->module);
    }

    /**
     * The configured number of model questions per module.
     */
    #[Computed]
    public function limit(): int
    {
        return $this->quota()->limit();
    }

    public function mount(Module $module, ?int $form_id = null, ?string $field_label = null): void
    {
        $user = $this->user();

        // A mentee may only chat about a module they can actually open. The
        // read is side-effect free: no checkpoint rows are created here.
        $checkpoint = app(ProgressServiceInterface::class)->checkpointFor($user, $module);

        if ($checkpoint === null || $checkpoint->status === CheckpointStatus::Locked) {
            throw new AuthorizationException('Modul ini masih terkunci.');
        }

        $this->module = $module;
        $this->field_label = $field_label;

        if ($form_id !== null) {
            $form = $module->practiceForms()->whereKey($form_id)->where('is_active', true)->first();

            if (! $form instanceof PracticeForm) {
                throw new AuthorizationException('Latihan ini tidak tersedia untuk modul tersebut.');
            }

            $this->form_id = $form->id;
        }
    }

    public function toggle(): void
    {
        $this->open = ! $this->open;
    }

    /**
     * Answer a suggested question.
     *
     * A suggestion with an admin-written answer is shown directly, without the
     * model and without using the quota. Any other suggestion is sent to the
     * AI Mentor like a typed question.
     */
    public function useSuggestion(int $index): void
    {
        $suggestion = $this->suggestions()[$index] ?? null;

        if ($suggestion === null) {
            return;
        }

        if ($suggestion['answer'] !== null) {
            $this->resetErrorBag('question');
            $this->pushMessage(['role' => 'user', 'content' => $suggestion['question']]);
            $this->pushMessage(['role' => 'assistant', 'content' => $suggestion['answer'], 'source' => 'suggestion']);

            return;
        }

        $this->question = $suggestion['question'];
        $this->send(app(ModuleMentorServiceInterface::class));
    }

    /**
     * Send the current question to the AI Mentor and append the reply.
     */
    public function send(ModuleMentorServiceInterface $mentor_service): void
    {
        $this->resetErrorBag('question');

        $question = trim($this->question);

        if ($question === '') {
            $this->addError('question', 'Tulis pertanyaanmu dulu ya.');

            return;
        }

        if (mb_strlen($question) > ModuleMentorService::MAX_QUESTION_LENGTH) {
            $this->addError('question', 'Pertanyaanmu terlalu panjang. Ringkas dulu ya.');

            return;
        }

        if ($this->remaining <= 0) {
            $this->addError('question', $this->quotaExhaustedMessage());

            return;
        }

        if ($this->rateLimited()) {
            $this->addError('question', 'Terlalu banyak permintaan. Tunggu sebentar lalu coba lagi.');

            return;
        }

        $history = $this->history();

        $this->pushMessage(['role' => 'user', 'content' => $question]);
        $this->question = '';
        $this->failed_question = null;

        $this->callMentor($mentor_service, $question, $history);
    }

    /**
     * Retry the last failed question without duplicating the transcript.
     */
    public function retry(ModuleMentorServiceInterface $mentor_service): void
    {
        if ($this->failed_question === null) {
            return;
        }

        if ($this->remaining <= 0) {
            $this->addError('question', $this->quotaExhaustedMessage());

            return;
        }

        if ($this->rateLimited()) {
            $this->addError('question', 'Terlalu banyak permintaan. Tunggu sebentar lalu coba lagi.');

            return;
        }

        // Drop the previous error bubble before asking again.
        $this->messages = array_values(array_filter(
            $this->messages,
            fn (array $message): bool => ($message['error'] ?? false) !== true,
        ));

        $question = $this->failed_question;
        $this->failed_question = null;

        $this->callMentor($mentor_service, $question, $this->history());
    }

    /**
     * Request a reply and append either the answer or a retryable error bubble.
     *
     * @param  array<int, array{role: string, content: string}>  $history
     */
    private function callMentor(ModuleMentorServiceInterface $mentor_service, string $question, array $history): void
    {
        $user = $this->user();
        $quota = $this->quota();

        // The question is reserved under a row lock before the model is called,
        // so parallel clicks can never go over the limit.
        if (! $quota->reserve($user, $this->module)) {
            $last = end($this->messages);

            // Another tab used the last question meanwhile: drop the bubble
            // that would otherwise wait for an answer that never comes.
            if ($last !== false && $last['role'] === 'user' && $last['content'] === $question) {
                array_pop($this->messages);
            }

            $this->failed_question = null;
            $this->addError('question', $this->quotaExhaustedMessage());
            unset($this->remaining);

            return;
        }

        try {
            $reply = $mentor_service->reply($this->module, $this->practiceForm(), $question, $history, $this->field_label);

            $this->pushMessage(['role' => 'assistant', 'content' => $reply->answer]);
        } catch (ValidationException $exception) {
            $quota->release($user, $this->module);

            foreach ($exception->errors() as $messages) {
                foreach ($messages as $message) {
                    $this->addError('question', $message);
                }
            }
        } catch (AiMentorException $exception) {
            // A failed model call never costs the mentee a question.
            $quota->release($user, $this->module);

            $this->failed_question = $question;
            $this->pushMessage([
                'role' => 'assistant',
                'content' => $exception->getMessage(),
                'error' => true,
            ]);
        } finally {
            unset($this->remaining);
        }
    }

    /**
     * The friendly message shown once the module quota is used up.
     */
    private function quotaExhaustedMessage(): string
    {
        return 'Kuota tanya AI Mentor untuk modul ini sudah habis. Kamu masih bisa membuka saran pertanyaan yang sudah ada jawabannya, atau baca ulang materi di langkah Pahami.';
    }

    /**
     * Resolve the practice form anchoring the conversation, if any.
     */
    private function practiceForm(): ?PracticeForm
    {
        if ($this->form_id === null) {
            return null;
        }

        $form = $this->module->practiceForms()->whereKey($this->form_id)->first();

        return $form instanceof PracticeForm ? $form : null;
    }

    /**
     * Get the quota service bound in the container.
     */
    private function quota(): MentorQuotaServiceInterface
    {
        return app(MentorQuotaServiceInterface::class);
    }

    /**
     * Append a chat bubble while keeping the component payload bounded.
     *
     * @param  array{role: string, content: string, error?: bool}  $message
     */
    private function pushMessage(array $message): void
    {
        $this->messages[] = $message;
        $this->messages = array_slice($this->messages, -self::MAX_TRANSCRIPT);
    }

    /**
     * Build the bounded history payload for the service.
     *
     * @return array<int, array{role: string, content: string}>
     */
    private function history(): array
    {
        $history = [];

        foreach ($this->messages as $message) {
            if (($message['error'] ?? false) === true) {
                continue;
            }

            $history[] = [
                'role' => $message['role'],
                'content' => $message['content'],
            ];
        }

        return array_slice($history, -ModuleMentorService::MAX_HISTORY_TURNS);
    }

    /**
     * Determine whether the mentee has exceeded the AI rate limit.
     */
    private function rateLimited(): bool
    {
        $key = 'ai-mentor:'.$this->user()->id;

        if (RateLimiter::tooManyAttempts($key, self::RATE_LIMIT_PER_MINUTE)) {
            return true;
        }

        RateLimiter::hit($key, 60);

        return false;
    }

    /**
     * Get the authenticated mentee.
     */
    private function user(): User
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            abort(403);
        }

        return $user;
    }

    public function render(): View
    {
        return view('livewire.learning.mentor-chat', [
            'suggestions' => $this->suggestions(),
            'remaining' => $this->remaining,
            'limit' => $this->limit,
        ]);
    }
}
