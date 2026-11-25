<?php

namespace App\Livewire\Tryout;

use App\Contracts\ActivityTrackerInterface;
use App\Contracts\EntitlementServiceInterface;
use App\Contracts\TryoutMentorQuotaServiceInterface;
use App\Contracts\TryoutMentorServiceInterface;
use App\Enums\ActivityName;
use App\Exceptions\AiMentorException;
use App\Exceptions\TryoutReviewLockedException;
use App\Models\TryoutAttempt;
use App\Models\User;
use App\Services\TryoutMentorService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Try Out mentor chat (Pro, child of the review page). Follows the
 * Learning\MentorChat pattern: the quota per attempt is reserved under a
 * row lock before the model is called and given back when it fails.
 * "Tanya mentor soal ini" anchors the chat to one answered question.
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
     * Code of the question the chat is about, or null for general theory.
     */
    #[Locked]
    public ?string $question_code = null;

    /**
     * Sequence label of the anchored question ("Reading · Soal 6").
     */
    #[Locked]
    public ?string $question_label = null;

    public string $question = '';

    /**
     * @var array<int, array{role: string, content: string, error?: bool}>
     */
    public array $messages = [];

    /**
     * Load a chat for the mentee's own attempt; Pro only.
     */
    public function mount(int $attempt_id, EntitlementServiceInterface $entitlements): void
    {
        $attempt = TryoutAttempt::query()->findOrFail($attempt_id);

        abort_unless($attempt->isOwnedBy($this->user()), 403);
        abort_unless($entitlements->can($this->user(), 'tryout_mentor'), 403);

        $this->attempt_id = $attempt_id;
    }

    /**
     * Anchor the chat to one answered question ("Tanya mentor soal ini").
     */
    #[On('tryout-ask-mentor')]
    public function focusQuestion(string $code, string $label): void
    {
        $this->question_code = mb_substr($code, 0, 32);
        $this->question_label = mb_substr($label, 0, 80);
        $this->question = __('Kenapa kunci soal ini benar, dan bagaimana cara mengerjakan soal sejenis?');
    }

    /**
     * Drop the question anchor.
     */
    public function clearFocus(): void
    {
        $this->question_code = null;
        $this->question_label = null;
    }

    /**
     * Remaining questions of this attempt.
     */
    #[Computed]
    public function remaining(): int
    {
        return app(TryoutMentorQuotaServiceInterface::class)->remaining($this->user(), $this->attempt());
    }

    /**
     * Send the question to the mentor within the quota.
     */
    public function send(TryoutMentorServiceInterface $mentor, TryoutMentorQuotaServiceInterface $quota, ActivityTrackerInterface $tracker): void
    {
        $this->resetErrorBag('question');
        $text = trim($this->question);

        if ($text === '' || mb_strlen($text) > TryoutMentorService::MAX_QUESTION_LENGTH) {
            $this->addError('question', $text === '' ? __('Tulis pertanyaanmu dulu ya.') : __('Pertanyaanmu terlalu panjang. Ringkas dulu ya.'));

            return;
        }

        $user = $this->user();
        $attempt = $this->attempt();

        if (! $quota->reserve($user, $attempt)) {
            $this->addError('question', __('Kuota pertanyaan mentor untuk Try Out ini sudah habis.'));

            return;
        }

        $history = array_values(array_filter($this->messages, fn (array $message): bool => ! ($message['error'] ?? false)));
        $this->pushMessage(['role' => 'user', 'content' => ($this->question_label ? '['.$this->question_label.'] ' : '').$text]);
        $this->question = '';

        try {
            $reply = $mentor->ask($user, $attempt, $text, $this->question_code, $history);
            $this->pushMessage(['role' => 'assistant', 'content' => $reply->answer]);
            $tracker->track(ActivityName::TryoutMentorAsked, ['tryout_attempt_id' => $attempt->id], $user);
        } catch (ValidationException $exception) {
            $quota->release($user, $attempt);
            array_pop($this->messages);
            $this->addError('question', (string) collect($exception->errors())->flatten()->first());
        } catch (AiMentorException|TryoutReviewLockedException $exception) {
            // A failed model call never costs the mentee a question.
            $quota->release($user, $attempt);
            $this->pushMessage(['role' => 'assistant', 'content' => $exception->getMessage(), 'error' => true]);
        } finally {
            unset($this->remaining);
        }
    }

    /**
     * Render the chat.
     *
     * @return View The livewire.tryout.mentor-chat view.
     */
    public function render(TryoutMentorQuotaServiceInterface $quota): View
    {
        return view('livewire.tryout.mentor-chat', ['limit' => $quota->limit()]);
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
     * The attempt.
     */
    private function attempt(): TryoutAttempt
    {
        return TryoutAttempt::query()->with('tryout')->findOrFail($this->attempt_id);
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
