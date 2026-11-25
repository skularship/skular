<?php

namespace App\Livewire\Tryout;

use App\Contracts\ActivityTrackerInterface;
use App\Contracts\TryoutResultServiceInterface;
use App\Data\TryoutReviewItemData;
use App\Enums\ActivityName;
use App\Enums\TryoutAttemptStatus;
use App\Exceptions\TryoutReviewLockedException;
use App\Models\TryoutAttempt;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Pro review of a finished attempt (route tryout.review): each answered
 * Listening/Reading question with the mentee's answer, the key and the
 * explanation, filters (subtest, wrong only) and "Tanya mentor soal ini".
 * Gratis sees a locked page; the key never reaches the HTML.
 */
#[Title('Pembahasan Try Out')]
class Review extends Component
{
    #[Locked]
    public int $attempt_id;

    /**
     * Subtest filter: "" (all), "listening" or "reading".
     */
    #[Url(as: 'subtes', except: '')]
    public string $skill = '';

    /**
     * Show only wrong answers.
     */
    #[Url(as: 'salah', except: false)]
    public bool $wrong_only = true;

    /**
     * Load the mentee's own finished attempt.
     */
    public function mount(TryoutAttempt $attempt, ActivityTrackerInterface $tracker): void
    {
        abort_unless($attempt->isOwnedBy($this->user()), 403);
        abort_if($attempt->status === TryoutAttemptStatus::InProgress, 404);

        $this->attempt_id = $attempt->id;

        $tracker->track(ActivityName::TryoutReviewOpened, ['tryout_attempt_id' => $attempt->id], $this->user());
    }

    /**
     * Render the review, or the locked page for Gratis.
     *
     * @return View The livewire.tryout.review view.
     */
    public function render(TryoutResultServiceInterface $results): View
    {
        $attempt = TryoutAttempt::query()->with('tryout')->findOrFail($this->attempt_id);
        abort_unless($attempt->isOwnedBy($this->user()), 403);

        $locked = false;
        /** @var Collection<int, TryoutReviewItemData> $items */
        $items = collect();

        try {
            $items = $results->review($this->user(), $attempt);
        } catch (TryoutReviewLockedException) {
            $locked = true;
        }

        $all = $items;
        $items = $items
            ->when($this->skill !== '', fn (Collection $rows): Collection => $rows->where('skill', $this->skill))
            ->when($this->wrong_only, fn (Collection $rows): Collection => $rows->where('is_correct', false))
            ->values();

        return view('livewire.tryout.review', [
            'attempt' => $attempt,
            'locked' => $locked,
            'items' => $items,
            'total' => $all->count(),
            'wrong' => $all->where('is_correct', false)->count(),
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
