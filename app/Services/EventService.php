<?php

namespace App\Services;

use App\Contracts\ActivityTrackerInterface;
use App\Contracts\EventServiceInterface;
use App\Enums\ActivityName;
use App\Enums\EventAudience;
use App\Enums\EventRegistrationStatus;
use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Skular events: visibility by audience (everyone, Pro, invitation tag),
 * registration under a row lock so the capacity can never be exceeded,
 * the H-1 link rule, attendance, post-event feedback and the .ics file.
 */
class EventService implements EventServiceInterface
{
    /**
     * Hours before the start from which the Zoom/Meet link is shown (H-1).
     */
    public const LINK_VISIBLE_HOURS_BEFORE = 24;

    /**
     * Maximum length of a registration answer.
     */
    public const MAX_ANSWER_LENGTH = 500;

    /**
     * @param  ActivityTrackerInterface  $tracker  Records event_registered and event_attended.
     */
    public function __construct(
        private readonly ActivityTrackerInterface $tracker,
    ) {}

    /**
     * Published events the mentee may see that have not ended, soonest first.
     *
     * @return Collection<int, Event>
     */
    public function upcoming(User $user): Collection
    {
        return Event::query()
            ->published()
            ->withSeatCount()
            ->where('ends_at', '>', now())
            ->orderBy('starts_at')
            ->get()
            ->filter(fn (Event $event): bool => $this->allowedByAudience($user, $event))
            ->values()
            ->toBase();
    }

    /**
     * Ended published events with a recording the mentee may watch, newest first.
     * For Gratis this includes the one Pro recording marked as free preview.
     *
     * @return Collection<int, Event>
     */
    public function recordings(User $user): Collection
    {
        return $this->endedWithRecording()
            ->filter(fn (Event $event): bool => $this->canWatchRecording($user, $event))
            ->values();
    }

    /**
     * Ended Pro recordings the mentee may not watch yet, shown locked with a link to Pro.
     * Empty for Pro mentees and while Premium is switched off.
     *
     * @return Collection<int, Event>
     */
    public function lockedRecordings(User $user): Collection
    {
        if (! config('pricing.enabled') || $user->hasPro()) {
            return collect();
        }

        return $this->endedWithRecording()
            ->filter(fn (Event $event): bool => $event->audience === EventAudience::Pro && ! $this->canWatchRecording($user, $event))
            ->values();
    }

    /**
     * Whether the mentee may open the event page to watch its recording: the event is
     * published, ended and has a recording, and the mentee is allowed by its audience,
     * registered for it, or it is the Pro recording marked as free preview.
     */
    public function canWatchRecording(User $user, Event $event): bool
    {
        if ($event->status !== EventStatus::Published || ! $event->hasEnded() || blank($event->recording_url)) {
            return false;
        }

        if ($event->audience === EventAudience::Pro && $event->is_free_preview) {
            return true;
        }

        return $this->canSee($user, $event);
    }

    /**
     * The nearest upcoming event for the dashboard card (a registered one first).
     */
    public function nextFor(User $user): ?Event
    {
        $upcoming = $this->upcoming($user);

        if ($upcoming->isEmpty()) {
            return null;
        }

        $registered_ids = $user->eventRegistrations()
            ->whereIn('event_id', $upcoming->pluck('id'))
            ->where('status', EventRegistrationStatus::Registered)
            ->pluck('event_id')
            ->all();

        return $upcoming->first(fn (Event $event): bool => in_array($event->id, $registered_ids, true))
            ?? $upcoming->first();
    }

    /**
     * Whether the mentee may see (and join) the event: published, and allowed by its audience.
     *
     * A mentee who already registered keeps access even when the audience changes later.
     */
    public function canSee(User $user, Event $event): bool
    {
        if ($event->status !== EventStatus::Published) {
            return false;
        }

        if ($this->allowedByAudience($user, $event)) {
            return true;
        }

        return $this->registrationFor($user, $event)?->isActive() ?? false;
    }

    /**
     * The mentee's registration for the event, if any (also cancelled ones).
     */
    public function registrationFor(User $user, Event $event): ?EventRegistration
    {
        return EventRegistration::query()
            ->where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->first();
    }

    /**
     * Whether every seat is taken.
     */
    public function isFull(Event $event): bool
    {
        if ($event->capacity === null) {
            return false;
        }

        return $this->seatsTaken($event) >= $event->capacity;
    }

    /**
     * Register the mentee. Registering twice returns the same row (no duplicate seat).
     *
     * The event row is locked while the seats are counted, so two mentees
     * taking the last seat at the same moment cannot both get it.
     *
     * @param  array<int, mixed>  $answers  Answers to Event::questions(), by index.
     *
     * @throws ValidationException When the event is closed, full, not for this mentee, or an answer is missing.
     */
    public function register(User $user, Event $event, array $answers = []): EventRegistration
    {
        if (! $this->canSee($user, $event)) {
            throw ValidationException::withMessages(['event' => 'Acara ini tidak tersedia untuk akunmu.']);
        }

        if ($event->hasStarted()) {
            throw ValidationException::withMessages(['event' => 'Pendaftaran acara ini sudah ditutup.']);
        }

        $clean_answers = $this->validatedAnswers($event, $answers);

        $registration = DB::transaction(function () use ($user, $event, $clean_answers): EventRegistration {
            /** @var Event $locked */
            $locked = Event::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();

            $existing = $this->registrationFor($user, $locked);

            if ($existing instanceof EventRegistration && $existing->isActive()) {
                return $existing;
            }

            if ($this->isFull($locked)) {
                throw ValidationException::withMessages(['event' => 'Maaf, kursi acara ini sudah penuh.']);
            }

            if ($existing instanceof EventRegistration) {
                $existing->update(['status' => EventRegistrationStatus::Registered, 'answers' => $clean_answers]);

                return $existing;
            }

            return EventRegistration::query()->create([
                'event_id' => $locked->id,
                'user_id' => $user->id,
                'answers' => $clean_answers,
                'status' => EventRegistrationStatus::Registered,
            ]);
        });

        if ($registration->wasRecentlyCreated || $registration->wasChanged('status')) {
            $this->tracker->track(ActivityName::EventRegistered, ['event_id' => $event->id, 'type' => $event->type->value], $user);
        }

        return $registration;
    }

    /**
     * Cancel the mentee's seat before the event starts.
     */
    public function cancel(User $user, Event $event): void
    {
        if ($event->hasStarted()) {
            return;
        }

        EventRegistration::query()
            ->where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->where('status', EventRegistrationStatus::Registered)
            ->update(['status' => EventRegistrationStatus::Cancelled]);
    }

    /**
     * Whether the Zoom/Meet link may be shown: registered, and from H-1 until the event ends.
     */
    public function canSeeLink(User $user, Event $event): bool
    {
        if (blank($event->location_url) || $event->status !== EventStatus::Published || $event->hasEnded()) {
            return false;
        }

        if (now()->lt($event->starts_at->subHours(self::LINK_VISIBLE_HOURS_BEFORE))) {
            return false;
        }

        return $this->registrationFor($user, $event)?->isActive() ?? false;
    }

    /**
     * Mark registrations as attended (admin bulk action) and record event_attended once each.
     *
     * @param  iterable<EventRegistration>  $registrations
     * @return int How many registrations changed to attended.
     */
    public function markAttended(iterable $registrations): int
    {
        $changed = 0;

        foreach ($registrations as $registration) {
            if ($registration->status === EventRegistrationStatus::Attended || $registration->status === EventRegistrationStatus::Cancelled) {
                continue;
            }

            $registration->update(['status' => EventRegistrationStatus::Attended]);
            $changed++;

            $this->tracker->track(ActivityName::EventAttended, ['event_id' => $registration->event_id], $registration->user);
        }

        return $changed;
    }

    /**
     * Store the mentee's 1-5 score and one-sentence feedback after the event.
     *
     * @throws ValidationException When the event has not ended, the mentee was not registered, or the input is invalid.
     */
    public function submitFeedback(User $user, Event $event, mixed $score, mixed $text): EventRegistration
    {
        $registration = $this->registrationFor($user, $event);

        if (! $registration instanceof EventRegistration || ! $registration->isActive()) {
            throw ValidationException::withMessages(['event_feedback' => 'Masukan hanya bisa diisi peserta yang terdaftar.']);
        }

        if (! $event->hasEnded()) {
            throw ValidationException::withMessages(['event_feedback' => 'Masukan bisa diisi setelah acara selesai.']);
        }

        $text = is_string($text) ? trim($text) : '';

        if (! is_numeric($score) || (int) $score < 1 || (int) $score > 5) {
            throw ValidationException::withMessages(['event_feedback_score' => 'Pilih angka 1 sampai 5.']);
        }

        if (mb_strlen($text) < 10) {
            throw ValidationException::withMessages(['event_feedback_text' => 'Tulis satu kalimat masukan, minimal 10 karakter.']);
        }

        $registration->update([
            'feedback_score' => (int) $score,
            'feedback_text' => Str::limit($text, 1000, ''),
        ]);

        return $registration;
    }

    /**
     * Build an iCalendar (.ics) file for "Tambah ke kalender".
     *
     * The meeting link is never put in the file: the event page shows it to
     * registered mentees from H-1, and the file links to that page.
     */
    public function calendarFile(Event $event): string
    {
        $escape = fn (string $value): string => str_replace(['\\', ';', ',', "\r\n", "\n"], ['\\\\', '\;', '\,', '\n', '\n'], $value);
        $format = fn ($time): string => $time->utc()->format('Ymd\THis\Z');
        $url = route('events.show', $event);

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Skular//Event//ID',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:event-'.$event->id.'@'.parse_url((string) config('app.url'), PHP_URL_HOST),
            'DTSTAMP:'.$format(now()),
            'DTSTART:'.$format($event->starts_at),
            'DTEND:'.$format($event->ends_at),
            'SUMMARY:'.$escape($event->title),
            'DESCRIPTION:'.$escape('Link acara muncul di halaman ini mulai H-1: '.$url),
            'URL:'.$url,
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        return implode("\r\n", $lines)."\r\n";
    }

    /**
     * Ended published events that have a recording, newest first.
     *
     * @return Collection<int, Event>
     */
    private function endedWithRecording(): Collection
    {
        return Event::query()
            ->published()
            ->where('ends_at', '<=', now())
            ->whereNotNull('recording_url')
            ->orderByDesc('starts_at')
            ->get()
            ->toBase();
    }

    /**
     * Whether the audience rule of the event lets this mentee in.
     */
    private function allowedByAudience(User $user, Event $event): bool
    {
        return match ($event->audience) {
            EventAudience::Semua => true,
            EventAudience::Pro => $user->hasPro(),
            EventAudience::Undangan => filled($event->invite_tag) && $user->hasTag((string) $event->invite_tag),
        };
    }

    /**
     * Count the seats taken (active registrations).
     */
    private function seatsTaken(Event $event): int
    {
        return EventRegistration::query()
            ->where('event_id', $event->id)
            ->whereIn('status', EventRegistrationStatus::holdingSeat())
            ->count();
    }

    /**
     * Validate the answers to the event's questions; every question is required.
     *
     * @param  array<int, mixed>  $answers
     * @return array<int, array{question: string, answer: string}>|null
     *
     * @throws ValidationException When an answer is missing or too long.
     */
    private function validatedAnswers(Event $event, array $answers): ?array
    {
        $questions = $event->questions();

        if ($questions === []) {
            return null;
        }

        $clean = [];
        $errors = [];

        foreach ($questions as $index => $question) {
            $answer = $answers[$index] ?? null;
            $answer = is_string($answer) ? trim(strip_tags($answer)) : '';

            if ($answer === '') {
                $errors['answers.'.$index] = 'Jawab pertanyaan ini dulu ya.';

                continue;
            }

            if (mb_strlen($answer) > self::MAX_ANSWER_LENGTH) {
                $errors['answers.'.$index] = 'Jawaban terlalu panjang, maksimal '.self::MAX_ANSWER_LENGTH.' karakter.';

                continue;
            }

            $clean[] = ['question' => $question, 'answer' => $answer];
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $clean;
    }
}
