<?php

namespace App\Livewire\Tryout;

use App\Contracts\TryoutAttemptServiceInterface;
use App\Enums\TryoutAttemptStatus;
use App\Enums\TryoutSectionStatus;
use App\Models\TryoutAttempt;
use App\Models\TryoutAttemptSection;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Try Out runner shell (route tryout.runner) on the focus layout. Shows the
 * transition screen between subtests ("Listening selesai" + Lanjut, the next
 * timer only starts on click) and renders the child component of the active
 * subtest: ObjectiveSection (Listening/Reading), WritingSection or SpeakingSection.
 * Sends the mentee to the result page once every subtest is closed.
 */
#[Layout('layouts.tryout')]
#[Title('Try Out')]
class Runner extends Component
{
    #[Locked]
    public int $attempt_id;

    /**
     * Load the mentee's own attempt; a finished one goes to the result page.
     */
    public function mount(TryoutAttempt $attempt): void
    {
        abort_unless($attempt->isOwnedBy(Auth::user() instanceof User ? Auth::user() : null), 403);

        $this->attempt_id = $attempt->id;

        if ($attempt->status !== TryoutAttemptStatus::InProgress) {
            $this->redirectRoute('tryout.result', ['attempt' => $attempt->id], navigate: false);
        }
    }

    /**
     * Start the timer of the next subtest ("Lanjut" on the transition screen).
     */
    public function startNext(TryoutAttemptServiceInterface $attempts): void
    {
        $attempts->startSection($this->attempt());
        $this->redirectWhenFinished();
    }

    /**
     * A child closed its subtest (finished, deadline, or "Selesaikan subtes").
     */
    #[On('section-closed')]
    public function sectionClosed(): void
    {
        $this->redirectWhenFinished();
    }

    /**
     * Render the transition screen or the active subtest.
     *
     * @return View The livewire.tryout.runner view.
     */
    public function render(TryoutAttemptServiceInterface $attempts): View
    {
        $attempt = $this->attempt()->load(['tryout', 'sections.section']);
        $current = $attempt->status === TryoutAttemptStatus::InProgress ? $attempts->currentSection($attempt) : null;

        $previous = $attempt->sections
            ->filter(fn (TryoutAttemptSection $section): bool => $section->status->isClosed())
            ->sortByDesc('submitted_at')
            ->first();

        return view('livewire.tryout.runner', [
            'attempt' => $attempt,
            'current' => $current,
            'previous' => $previous,
            'previous_answered' => $previous?->answers()->whereNotNull('answered_at')->count() ?? 0,
            'show_transition' => $current !== null && $current->status === TryoutSectionStatus::Pending,
        ]);
    }

    /**
     * Go to the result page once the attempt is completed.
     */
    private function redirectWhenFinished(): void
    {
        $attempt = $this->attempt();

        $attempts = app(TryoutAttemptServiceInterface::class);

        if ($attempt->status === TryoutAttemptStatus::InProgress && $attempts->currentSection($attempt) === null) {
            $attempts->complete($attempt);
            $attempt->refresh();
        }

        if ($attempt->status !== TryoutAttemptStatus::InProgress) {
            $this->redirectRoute('tryout.result', ['attempt' => $attempt->id], navigate: false);
        }
    }

    /**
     * The attempt, re-checked against the signed-in mentee on every request.
     */
    private function attempt(): TryoutAttempt
    {
        $attempt = TryoutAttempt::query()->findOrFail($this->attempt_id);

        abort_unless($attempt->isOwnedBy(Auth::user() instanceof User ? Auth::user() : null), 403);

        return $attempt;
    }
}
