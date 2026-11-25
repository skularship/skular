<?php

namespace App\Livewire\Iq;

use App\Contracts\IqAttemptServiceInterface;
use App\Enums\IqAttemptStatus;
use App\Exceptions\IqAnswerRejectedException;
use App\Models\IqAttempt;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Tes IQ runner (route iq.runner) on the focus layout: one question at a
 * time with large A-D buttons, a server-timed countdown, a progress bar of
 * answered questions and free navigation (Sebelumnya / Berikutnya / number
 * grid). Every pick is saved at once and can be changed until "Kirim"; the
 * deadline closes the attempt on the server. Support messages come only
 * from the mentee's own counts. The component only holds
 * IqQuestionViewData, never the key.
 */
#[Layout('layouts.tryout')]
#[Title('Tes IQ Latihan')]
class Runner extends Component
{
    #[Locked]
    public int $attempt_id;

    /**
     * The question on screen as IqQuestionViewData::toArray().
     *
     * @var array<string, mixed>
     */
    #[Locked]
    public array $question = [];

    /**
     * Positions (1-based) with a saved choice.
     *
     * @var array<int, int>
     */
    #[Locked]
    public array $answered = [];

    /**
     * Load the mentee's own attempt on the first unanswered question.
     */
    public function mount(IqAttempt $attempt, IqAttemptServiceInterface $attempts): void
    {
        abort_unless($attempt->isOwnedBy($this->user()), 403);

        $this->attempt_id = $attempt->id;

        if ($attempts->closeIfExpired($attempt) || $attempt->status !== IqAttemptStatus::InProgress) {
            $this->redirectRoute('iq.result', ['attempt' => $attempt->id], navigate: false);

            return;
        }

        $this->answered = $attempts->answeredPositions($attempt);
        $first_open = collect(range(1, max(1, $attempt->total_questions)))->first(fn (int $position): bool => ! in_array($position, $this->answered, true));

        $this->show($attempts, $first_open ?? 1);
    }

    /**
     * Save the picked letter of the question on screen.
     */
    public function choose(string $letter, IqAttemptServiceInterface $attempts): void
    {
        $key = 'iq:answer:'.Auth::id();

        if (RateLimiter::tooManyAttempts($key, (int) config('iq.answers_per_minute', 120))) {
            $this->dispatch('toast', variant: 'warning', text: __('Terlalu cepat. Tunggu sebentar lalu pilih lagi.'));

            return;
        }

        RateLimiter::hit($key, 60);

        $attempt = $this->attempt();

        try {
            $result = $attempts->answer($attempt, (int) $this->question['position'], $letter);
        } catch (IqAnswerRejectedException $exception) {
            $this->dispatch('toast', variant: 'info', text: $exception->getMessage());
            $this->redirectWhenClosed();

            return;
        }

        $this->question['choice'] = strtoupper(trim($letter));
        $this->answered = $attempts->answeredPositions($attempt);

        if ($result->support_message !== null) {
            $this->dispatch('iq-support', message: $result->support_message);
        }
    }

    /**
     * Show the question at a position (number grid).
     */
    public function goTo(int $position, IqAttemptServiceInterface $attempts): void
    {
        if ($this->redirectWhenClosed()) {
            return;
        }

        $this->show($attempts, $position);
    }

    /**
     * Show the next question.
     */
    public function next(IqAttemptServiceInterface $attempts): void
    {
        $this->goTo((int) $this->question['position'] + 1, $attempts);
    }

    /**
     * Show the previous question.
     */
    public function previous(IqAttemptServiceInterface $attempts): void
    {
        $this->goTo((int) $this->question['position'] - 1, $attempts);
    }

    /**
     * "Kirim": close the attempt and open the result page.
     */
    public function submit(IqAttemptServiceInterface $attempts): void
    {
        $attempts->finish($this->attempt());

        $this->redirectRoute('iq.result', ['attempt' => $this->attempt_id], navigate: false);
    }

    /**
     * Called by the timer when the deadline passed; the server decides.
     */
    public function expire(IqAttemptServiceInterface $attempts): void
    {
        $attempt = $this->attempt();

        if ($attempts->closeIfExpired($attempt) || $attempt->status !== IqAttemptStatus::InProgress) {
            $this->dispatch('toast', variant: 'info', text: __('Waktu habis. Jawaban yang sudah tersimpan otomatis dikirim.'));
            $this->redirectRoute('iq.result', ['attempt' => $this->attempt_id], navigate: false);
        }
    }

    /**
     * Render the runner.
     *
     * @return View The livewire.iq.runner view.
     */
    public function render(): View
    {
        $attempt = $this->attempt();
        $total = max(1, $attempt->total_questions);

        return view('livewire.iq.runner', [
            'attempt' => $attempt,
            'total' => $attempt->total_questions,
            'answered_count' => count($this->answered),
            'progress' => (int) round(count($this->answered) / $total * 100),
            'deadline' => $attempt->deadline_at->toIso8601String(),
            'server_now' => now()->toIso8601String(),
            'grace' => (int) config('iq.answer_grace_seconds', 5),
        ]);
    }

    /**
     * Load a question (the server clamps the position and records the visit).
     */
    private function show(IqAttemptServiceInterface $attempts, int $position): void
    {
        $this->question = $attempts->question($this->attempt(), $position)->toArray();
    }

    /**
     * Go to the result page when the attempt is no longer running.
     *
     * @return bool True when a redirect was issued.
     */
    private function redirectWhenClosed(): bool
    {
        $attempt = $this->attempt();
        app(IqAttemptServiceInterface::class)->closeIfExpired($attempt);

        if ($attempt->status === IqAttemptStatus::InProgress) {
            return false;
        }

        $this->redirectRoute('iq.result', ['attempt' => $this->attempt_id], navigate: false);

        return true;
    }

    /**
     * The attempt, re-checked against the signed-in mentee on every request.
     */
    private function attempt(): IqAttempt
    {
        $attempt = IqAttempt::query()->findOrFail($this->attempt_id);

        abort_unless($attempt->isOwnedBy($this->user()), 403);

        return $attempt;
    }

    /**
     * The signed-in mentee.
     */
    private function user(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }
}
