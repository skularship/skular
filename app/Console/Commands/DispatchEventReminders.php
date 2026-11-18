<?php

namespace App\Console\Commands;

use App\Enums\EventRegistrationStatus;
use App\Enums\EventStatus;
use App\Mail\EventReminderMail;
use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Runs every 15 minutes: queues the H-1 day and H-1 hour reminder emails
 * (queue "reminders") for every active registration of a published event.
 *
 * Idempotent: the reminder kind is written to event_registrations.reminded_at
 * under a row lock before the email is queued, so a reminder is never sent
 * twice, even when two schedulers overlap. A registration made inside the
 * last hour gets only the hour reminder.
 */
class DispatchEventReminders extends Command
{
    /**
     * @var string
     */
    protected $signature = 'events:remind';

    /**
     * @var string
     */
    protected $description = 'Queue H-1 day and H-1 hour reminder emails for upcoming events';

    /**
     * Reminder kinds and how many minutes before the start they are due.
     *
     * @var array<string, int>
     */
    public const WINDOWS = [
        'hour' => 60,
        'day' => 24 * 60,
    ];

    /**
     * Find due registrations and queue their reminders.
     */
    public function handle(): int
    {
        $count = 0;

        $events = Event::query()
            ->where('status', EventStatus::Published)
            ->where('starts_at', '>', now())
            ->where('starts_at', '<=', now()->addMinutes(self::WINDOWS['day']))
            ->get();

        foreach ($events as $event) {
            // The closest window wins: inside the last hour only "hour" is sent.
            $kind = $event->starts_at->lte(now()->addMinutes(self::WINDOWS['hour'])) ? 'hour' : 'day';

            $pending = EventRegistration::query()
                ->where('event_id', $event->id)
                ->where('status', EventRegistrationStatus::Registered)
                ->get(['id']);

            foreach ($pending as $row) {
                if ($this->claim($row->id, $kind)) {
                    $registration = EventRegistration::query()->with(['event', 'user'])->whereKey($row->id)->firstOrFail();

                    Mail::to($registration->user)->queue(new EventReminderMail($registration, $kind));
                    $count++;
                }
            }
        }

        $this->info("{$count} pengingat event diantrekan.");

        return self::SUCCESS;
    }

    /**
     * Mark the reminder as sent under a row lock; false when it was already sent.
     *
     * The hour reminder also marks the day reminder, so a late day run never follows it.
     */
    private function claim(int $registration_id, string $kind): bool
    {
        return DB::transaction(function () use ($registration_id, $kind): bool {
            $registration = EventRegistration::query()->whereKey($registration_id)->lockForUpdate()->first();

            if (! $registration instanceof EventRegistration || $registration->wasReminded($kind)) {
                return false;
            }

            $reminded = $registration->reminded_at ?? [];
            $reminded[$kind] = now()->toIso8601String();

            if ($kind === 'hour') {
                $reminded['day'] ??= $reminded[$kind];
            }

            $registration->reminded_at = $reminded;
            $registration->save();

            return true;
        });
    }
}
