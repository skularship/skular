<?php

namespace App\Livewire\Events;

use App\Contracts\EventServiceInterface;
use App\Models\Event;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Event detail (route events.show): description, "Daftar sekarang" with the
 * short registration questions, the meeting link from H-1 for registered
 * mentees, "Tambah ke kalender", and the 1-5 feedback card after the event.
 * Every rule is checked again in EventService.
 */
#[Title('Detail Event')]
class Show extends Component
{
    #[Locked]
    public Event $event;

    /**
     * Whether the registration questions are shown (after "Daftar sekarang").
     */
    public bool $answering = false;

    /**
     * Answers to the registration questions, by index.
     *
     * @var array<int, string>
     */
    public array $answers = [];

    /**
     * Post-event score 1-5.
     */
    public ?int $feedback_score = null;

    /**
     * Post-event one-sentence feedback.
     */
    public string $feedback_text = '';

    /**
     * Show the event; drafts, cancelled events and events outside the mentee's audience return 404.
     */
    public function mount(Event $event, EventServiceInterface $events): void
    {
        abort_unless($events->canSee($this->user(), $event), 404);

        $this->event = $event;

        $registration = $events->registrationFor($this->user(), $event);

        $this->feedback_score = $registration?->feedback_score;
        $this->feedback_text = (string) $registration?->feedback_text;
    }

    /**
     * "Daftar sekarang": register directly, or open the questions first when the event has any.
     */
    public function startRegistration(EventServiceInterface $events): void
    {
        if ($this->event->questions() !== [] && ! $this->answering) {
            $this->answering = true;

            return;
        }

        $this->register($events);
    }

    /**
     * Register with the given answers.
     */
    public function register(EventServiceInterface $events): void
    {
        $this->resetErrorBag();

        $events->register($this->user(), $this->event, $this->answers);

        $this->answering = false;

        $this->dispatch('toast', variant: 'success', text: __('Kamu terdaftar. Link acara muncul di sini H-1.'));
    }

    /**
     * Cancel the seat so someone else can take it.
     */
    public function cancel(EventServiceInterface $events): void
    {
        $events->cancel($this->user(), $this->event);

        $this->dispatch('toast', variant: 'info', text: __('Pendaftaranmu dibatalkan.'));
    }

    /**
     * Send the post-event score and feedback.
     */
    public function sendFeedback(EventServiceInterface $events): void
    {
        $this->resetErrorBag();

        $events->submitFeedback($this->user(), $this->event, $this->feedback_score, $this->feedback_text);

        $this->dispatch('toast', variant: 'success', text: __('Terima kasih, masukanmu tersimpan!'));
    }

    /**
     * Get the authenticated mentee.
     */
    private function user(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        return $user;
    }

    /**
     * Render the event detail (route events.show).
     *
     * @return View The livewire.events.show view.
     */
    public function render(EventServiceInterface $events): View
    {
        $registration = $events->registrationFor($this->user(), $this->event);

        return view('livewire.events.show', [
            'registration' => $registration,
            'is_registered' => $registration?->isActive() ?? false,
            'is_full' => $events->isFull($this->event),
            'can_see_link' => $events->canSeeLink($this->user(), $this->event),
            'questions' => $this->event->questions(),
        ]);
    }
}
