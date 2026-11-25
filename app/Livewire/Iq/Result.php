<?php

namespace App\Livewire\Iq;

use App\Contracts\EntitlementServiceInterface;
use App\Contracts\IqAttemptServiceInterface;
use App\Contracts\IqResultServiceInterface;
use App\Enums\IqAttemptStatus;
use App\Models\IqAttempt;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Result page of a Tes IQ attempt (route iq.result). Without paid access it
 * only shows how many questions were answered and the call to open access;
 * no score, key or explanation is loaded. With paid access: total and per
 * subtest scores ("Numerik 5/7 (71%)"), the category, the review with key
 * and explanation, the material link and the AI mentor. Never an IQ number;
 * the disclaimer is always shown.
 */
#[Title('Hasil Tes IQ')]
class Result extends Component
{
    #[Locked]
    public int $attempt_id;

    /**
     * Load the mentee's own attempt; one still running goes back to the runner.
     */
    public function mount(IqAttempt $attempt, IqAttemptServiceInterface $attempts): void
    {
        abort_unless($attempt->isOwnedBy($this->user()), 403);

        $this->attempt_id = $attempt->id;
        $attempts->closeIfExpired($attempt);

        if ($attempt->status === IqAttemptStatus::InProgress) {
            $this->redirectRoute('iq.runner', ['attempt' => $attempt->id], navigate: false);
        }
    }

    /**
     * Render the result.
     *
     * @return View The livewire.iq.result view.
     */
    public function render(IqResultServiceInterface $results, EntitlementServiceInterface $entitlements): View
    {
        $attempt = $this->attempt()->load('test');
        $user = $this->user();
        $result = $results->result($attempt, $user);

        return view('livewire.iq.result', [
            'attempt' => $attempt,
            'test' => $attempt->test,
            'result' => $result,
            'review' => $result->unlocked && $attempt->status->isClosed() ? $results->review($attempt, $user) : [],
            'premium_enabled' => $entitlements->premiumEnabled(),
        ]);
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
    private function user(): User
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            abort(403);
        }

        return $user;
    }
}
