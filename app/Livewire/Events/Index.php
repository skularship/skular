<?php

namespace App\Livewire\Events;

use App\Contracts\EventServiceInterface;
use App\Enums\EventRegistrationStatus;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * "Event" page (route events.index): upcoming webinars, intensive classes and
 * personal mentoring the mentee may join, and a "Rekaman" tab with recordings.
 * Gratis also sees the one Pro recording marked as free preview, and the other
 * Pro recordings locked with a link to the Premium page.
 */
#[Title('Event')]
class Index extends Component
{
    /**
     * Active tab: "akan-datang" or "rekaman".
     */
    #[Url(as: 'tab', except: 'akan-datang')]
    public string $tab = 'akan-datang';

    /**
     * Render the event list (route events.index).
     *
     * @return View The livewire.events.index view.
     */
    public function render(EventServiceInterface $events): View
    {
        /** @var User $user */
        $user = Auth::user();

        $show_recordings = $this->tab === 'rekaman';

        $list = $show_recordings ? $events->recordings($user) : $events->upcoming($user);

        return view('livewire.events.index', [
            'events' => $list,
            'locked_recordings' => $show_recordings ? $events->lockedRecordings($user) : collect(),
            'show_recordings' => $show_recordings,
            'is_pro' => $user->hasPro(),
            'registered_ids' => $user->eventRegistrations()
                ->whereIn('status', [EventRegistrationStatus::Registered, EventRegistrationStatus::Attended])
                ->pluck('event_id')
                ->all(),
        ]);
    }
}
