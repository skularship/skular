<?php

namespace App\Livewire\Iq;

use App\Contracts\IqAttemptServiceInterface;
use App\Contracts\IqResultServiceInterface;
use App\Enums\IqAttemptStatus;
use App\Exceptions\IqNotAvailableException;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Start page of the Tes IQ Latihan (route iq.index): what the test is
 * (questions, minutes, subtests), the disclaimer, "Mulai" (or "Lanjutkan"
 * for the attempt in progress) and the mentee's previous attempts. Free
 * for every signed-in mentee; one active attempt per account.
 */
#[Title('Tes IQ Latihan')]
class Index extends Component
{
    /**
     * Start an attempt (or resume the active one) and open the runner.
     */
    public function start(IqAttemptServiceInterface $attempts): void
    {
        $test = $attempts->currentTest();

        if ($test === null) {
            $this->dispatch('toast', variant: 'warning', text: __('Tes IQ belum tersedia. Cek lagi nanti ya.'));

            return;
        }

        try {
            $attempt = $attempts->start($this->user(), $test);
        } catch (IqNotAvailableException $exception) {
            $this->dispatch('toast', variant: 'warning', text: $exception->getMessage());

            return;
        }

        $this->redirectRoute('iq.runner', ['attempt' => $attempt->id], navigate: false);
    }

    /**
     * Render the start page.
     *
     * @return View The livewire.iq.index view.
     */
    public function render(IqAttemptServiceInterface $attempts, IqResultServiceInterface $results): View
    {
        $user = $this->user();
        $test = $attempts->currentTest();

        return view('livewire.iq.index', [
            'test' => $test,
            'question_count' => $test?->questions()->where('is_active', true)->count() ?? 0,
            'active' => $attempts->activeAttempt($user),
            'history' => $user->iqAttempts()
                ->where('status', '!=', IqAttemptStatus::InProgress->value)
                ->latest('id')
                ->limit(5)
                ->get(),
            'unlocked' => $results->canSeeResults($user),
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
