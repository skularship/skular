<?php

namespace App\Livewire\Tryout;

use App\Contracts\TryoutCatalogServiceInterface;
use App\Enums\TryoutAttemptStatus;
use App\Models\Tryout;
use App\Models\TryoutAttempt;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * "Try Out" page (route tryout.index): one card per published Try Out with
 * its four subtests, total duration, labels (Versi uji / Pro) and the
 * mentee's attempt status (Mulai, Lanjutkan, Lihat hasil).
 */
#[Title('Try Out')]
class Index extends Component
{
    /**
     * Render the Try Out list.
     *
     * @return View The livewire.tryout.index view.
     */
    public function render(TryoutCatalogServiceInterface $catalog): View
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            abort(403);
        }

        $tryouts = $catalog->published($user);

        /** @var array<int, TryoutAttempt> $latest */
        $latest = $user->tryoutAttempts()
            ->whereIn('tryout_id', $tryouts->pluck('id')->all())
            ->latest('id')
            ->get()
            ->unique('tryout_id')
            ->keyBy('tryout_id')
            ->all();

        return view('livewire.tryout.index', [
            'cards' => $tryouts->map(fn (Tryout $tryout): array => [
                'tryout' => $tryout,
                'attempt' => $latest[$tryout->id] ?? null,
                'in_progress' => ($latest[$tryout->id] ?? null)?->status === TryoutAttemptStatus::InProgress,
            ]),
        ]);
    }
}
