<?php

namespace App\Contracts;

use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Skular events (webinar, intensive class, personal mentoring): who may see
 * them, registration with capacity and audience checks, the H-1 link rule,
 * attendance, post-event feedback, and the calendar file.
 */
interface EventServiceInterface
{
    /**
     * Published events the mentee may see that have not ended, soonest first.
     *
     * @return Collection<int, Event>
     */
    public function upcoming(User $user): Collection;

    /**
     * Ended published events with a recording the mentee may watch, newest first.
     * For Gratis this includes the one Pro recording marked as free preview.
     *
     * @return Collection<int, Event>
     */
    public function recordings(User $user): Collection;

    /**
     * Ended Pro recordings the mentee may not watch yet, shown locked with a link to Pro.
     * Empty for Pro mentees and while Premium is switched off.
     *
     * @return Collection<int, Event>
     */
    public function lockedRecordings(User $user): Collection;

    /**
     * Whether the mentee may open the event page to watch its recording.
     */
    public function canWatchRecording(User $user, Event $event): bool;

    /**
     * The nearest upcoming event for the dashboard card (a registered one first).
     */
    public function nextFor(User $user): ?Event;

    /**
     * Whether the mentee may see (and join) the event: published, and allowed by its audience.
     */
    public function canSee(User $user, Event $event): bool;

    /**
     * The mentee's registration for the event, if any (also cancelled ones).
     */
    public function registrationFor(User $user, Event $event): ?EventRegistration;

    /**
     * Whether every seat is taken.
     */
    public function isFull(Event $event): bool;

    /**
     * Register the mentee. Registering twice returns the same row (no duplicate seat).
     *
     * @param  array<int, mixed>  $answers  Answers to Event::questions(), by index.
     *
     * @throws ValidationException When the event is closed, full, not for this mentee, or an answer is missing.
     */
    public function register(User $user, Event $event, array $answers = []): EventRegistration;

    /**
     * Cancel the mentee's seat before the event starts.
     */
    public function cancel(User $user, Event $event): void;

    /**
     * Whether the Zoom/Meet link may be shown: registered, and from H-1 until the event ends.
     */
    public function canSeeLink(User $user, Event $event): bool;

    /**
     * Mark registrations as attended (admin bulk action) and record event_attended once each.
     *
     * @param  iterable<EventRegistration>  $registrations
     * @return int How many registrations changed to attended.
     */
    public function markAttended(iterable $registrations): int;

    /**
     * Store the mentee's 1-5 score and one-sentence feedback after the event.
     *
     * @throws ValidationException When the event has not ended, the mentee was not registered, or the input is invalid.
     */
    public function submitFeedback(User $user, Event $event, mixed $score, mixed $text): EventRegistration;

    /**
     * Build an iCalendar (.ics) file for "Tambah ke kalender".
     */
    public function calendarFile(Event $event): string;
}
