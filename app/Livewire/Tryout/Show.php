<?php

namespace App\Livewire\Tryout;

use App\Contracts\TryoutAttemptServiceInterface;
use App\Contracts\TryoutCatalogServiceInterface;
use App\Enums\TryoutMode;
use App\Exceptions\TryoutNotAvailableException;
use App\Models\Tryout;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Intro page of a Try Out (route tryout.show): the four subtests, rules,
 * mode choice (Latihan / Ujian), device checklist, disclaimer and the start
 * button. Resumes an attempt in progress instead of starting a new one.
 */
#[Title('Try Out')]
class Show extends Component
{
    #[Locked]
    public Tryout $tryout;

    /**
     * Chosen mode: "practice" or "exam".
     */
    public string $mode = 'practice';

    /**
     * Load a published Try Out; drafts are 404 even with a guessed URL.
     */
    public function mount(Tryout $tryout): void
    {
        abort_unless($tryout->status->isPublished(), 404);

        $this->tryout = $tryout->load('sections');
    }

    /**
     * Start (or resume) the attempt and open the runner.
     */
    public function start(TryoutAttemptServiceInterface $attempts): void
    {
        $mode = TryoutMode::tryFrom($this->mode) ?? TryoutMode::Practice;

        try {
            $attempt = $attempts->start($this->user(), $this->tryout, $mode);
        } catch (TryoutNotAvailableException $exception) {
            $this->addError('start', $exception->getMessage());

            return;
        }

        $this->redirectRoute('tryout.runner', ['attempt' => $attempt->id], navigate: false);
    }

    /**
     * Render the intro page.
     *
     * @return View The livewire.tryout.show view.
     */
    public function render(TryoutCatalogServiceInterface $catalog): View
    {
        $user = $this->user();

        return view('livewire.tryout.show', [
            'active_attempt' => $catalog->activeAttempt($user, $this->tryout),
            'latest_completed' => $catalog->latestCompletedAttempt($user, $this->tryout),
            'blocked_reason' => $catalog->blockedReason($user, $this->tryout),
            'modes' => TryoutMode::cases(),
        ]);
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
