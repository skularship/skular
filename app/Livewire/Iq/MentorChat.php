<?php

namespace App\Livewire\Iq;

use App\Contracts\ActivityTrackerInterface;
use App\Contracts\IqMentorQuotaServiceInterface;
use App\Contracts\IqMentorServiceInterface;
use App\Contracts\IqResultServiceInterface;
use App\Enums\ActivityName;
use App\Exceptions\AiMentorException;
use App\Exceptions\IqLockedException;
use App\Models\IqAttempt;
use App\Models\User;
use App\Services\IqMentorService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Tes IQ mentor chat (paid access, child of the result page). Follows the
 * Tryout\MentorChat pattern, with the quota per account per day: reserved
 * under a row lock before the model is called and given back when it fails.
 * "Tanya mentor soal ini" anchors the chat to one question of the attempt.
 */
class MentorChat extends Component
{
    /**
     * Turns kept in the transcript.
     */
    private const MAX_TRANSCRIPT = 30;

    #[Locked]
    public int $attempt_id;

    /**
     * Position (1-based) of the question the chat is about, or null.
     */
    #[Locked]
    public ?int $question_position = null;

    public string $question = '';

    /**
     * @var array<int, array{role: string, content: string, error?: bool}>
     */
    public array $messages = [];

    /**
     * Load a chat for the mentee's own closed attempt; paid access only.
     */
    public function mount(int $attempt_id, IqResultServiceInterface $results): void
    {
        $attempt = IqAttempt::query()->findOrFail($attempt_id);

        abort_unless($attempt->isOwnedBy($this->user()), 403);
        abort_unless($attempt->status->isClosed(), 403);
        abort_unless($results->canSeeResults($this->user()), 403);

        $this->attempt_id = $attempt_id;
    }

    /**
     * Anchor the chat to one question ("Tanya mentor soal ini").
     */
    #[On('iq-ask-mentor')]
    public function focusQuestion(int $position): void
    {
        $total = $this->attempt()->total_questions;
        $this->question_position = $position >= 1 && $position <= $total ? $position : null;

        if ($this->question_position !== null) {
            $this->question = __('Kenapa jawaban soal ini benar, dan bagaimana cara mengerjakan soal sejenis?');
        }
    }

    /**
     * Drop the question anchor.
     */
    public function clearFocus(): void
    {
        $this->question_position = null;
    }

    /**
     * Remaining questions today.
     */
    #[Computed]
    public function remaining(): int
    {
        return app(IqMentorQuotaServiceInterface::class)->remaining($this->user());
    }

    /**
     * Send the question to the mentor within today's quota.
     */
    public function send(IqMentorServiceInterface $mentor, IqMentorQuotaServiceInterface $quota, ActivityTrackerInterface $tracker): void
    {
        $this->resetErrorBag('question');
        $text = trim($this->question);

        if ($text === '' || mb_strlen($text) > IqMentorService::MAX_QUESTION_LENGTH) {
            $this->addError('question', $text === '' ? __('Tulis pertanyaanmu dulu ya.') : __('Pertanyaanmu terlalu panjang. Ringkas dulu ya.'));

            return;
        }

        $user = $this->user();
        $attempt = $this->attempt();

        if (! $quota->reserve($user)) {
            $this->addError('question', __('Kuota pertanyaan mentor hari ini sudah habis. Coba lagi besok ya.'));

            return;
        }

        $history = array_values(array_filter($this->messages, fn (array $message): bool => ! ($message['error'] ?? false)));
        $label = $this->question_position !== null ? '['.__('Soal :n', ['n' => $this->question_position]).'] ' : '';
        $this->pushMessage(['role' => 'user', 'content' => $label.$text]);
        $this->question = '';

        try {
            $reply = $mentor->ask($user, $attempt, $text, $this->question_position, $history);
            $this->pushMessage(['role' => 'assistant', 'content' => $reply->answer]);
            $tracker->track(ActivityName::IqMentorAsked, ['iq_attempt_id' => $attempt->id], $user);
        } catch (ValidationException $exception) {
            $quota->release($user);
            array_pop($this->messages);
            $this->addError('question', (string) collect($exception->errors())->flatten()->first());
        } catch (AiMentorException|IqLockedException $exception) {
            // A failed model call never costs the mentee a question.
            $quota->release($user);
            $this->pushMessage(['role' => 'assistant', 'content' => $exception->getMessage(), 'error' => true]);
        } finally {
            unset($this->remaining);
        }
    }

    /**
     * Render the chat.
     *
     * @return View The livewire.iq.mentor-chat view.
     */
    public function render(IqMentorQuotaServiceInterface $quota): View
    {
        return view('livewire.iq.mentor-chat', ['limit' => $quota->limit()]);
    }

    /**
     * Append a chat turn, keeping the transcript bounded.
     *
     * @param  array{role: string, content: string, error?: bool}  $message
     */
    private function pushMessage(array $message): void
    {
        $this->messages[] = $message;
        $this->messages = array_slice($this->messages, -self::MAX_TRANSCRIPT);
    }

    /**
     * The attempt, re-checked against the signed-in mentee.
     */
    private function attempt(): IqAttempt
    {
        $attempt = IqAttempt::query()->with('test')->findOrFail($this->attempt_id);

        abort_unless($attempt->isOwnedBy($this->user()), 403);

        return $attempt;
    }

    /**
     * The signed-in mentee.
     */
    private function user(): User
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            abort(403);
        }

        return $user;
    }
}
