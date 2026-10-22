<?php

namespace App\Livewire\Learning;

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
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * A lightweight AI Mentor panel anchored to the module practice form.
 *
 * The chat is intentionally ephemeral: history lives in component state and is
 * never persisted, so nothing leaks between mentees and no schema is needed.
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
     * @var array<int, array{role: string, content: string, error?: bool}>
     */
    public array $messages = [];

    /**
     * The last question that failed, so the mentee can retry without retyping.
     */
    public ?string $failed_question = null;

    /**
     * Quick prompts offered in the empty state.
     *
     * @return array<int, string>
     */
    public function quickPrompts(): array
    {
        return [
            'Jelaskan kembali materi ini dengan bahasa sederhana.',
            'Bagaimana cara memulai jawaban latihan ini?',
            'Apa yang perlu saya perhatikan pada praktik ini?',
            'Bantu saya memahami instruksinya.',
        ];
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
     * Seed the input box from a quick prompt and send it right away.
     */
    public function useQuickPrompt(string $prompt): void
    {
        $this->question = $prompt;
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
        $form = $this->form_id === null
            ? null
            : $this->module->practiceForms()->whereKey($this->form_id)->first();

        try {
            $reply = $mentor_service->reply($this->module, $form, $question, $history, $this->field_label);

            $this->pushMessage(['role' => 'assistant', 'content' => $reply->answer]);
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $messages) {
                foreach ($messages as $message) {
                    $this->addError('question', $message);
                }
            }
        } catch (AiMentorException $exception) {
            $this->failed_question = $question;
            $this->pushMessage([
                'role' => 'assistant',
                'content' => $exception->getMessage().' Coba lagi ya.',
                'error' => true,
            ]);
        }
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
            'quick_prompts' => $this->quickPrompts(),
        ]);
    }
}
