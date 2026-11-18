<?php

namespace App\Mail;

use App\Models\EventRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Reminder for a registered mentee: the event starts tomorrow ("day") or in
 * an hour ("hour"). Holds the meeting link (allowed from H-1) and a link to
 * download the calendar file. Queued on "reminders" by events:remind.
 */
class EventReminderMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    /**
     * Keep the registration and the reminder kind ("day" or "hour").
     */
    public function __construct(public EventRegistration $registration, public string $kind)
    {
        $this->onQueue('reminders');
    }

    /**
     * Build the subject line.
     */
    public function envelope(): Envelope
    {
        $title = $this->registration->event->title;

        return new Envelope(
            subject: $this->kind === 'hour'
                ? __('1 jam lagi: :title', ['title' => $title])
                : __('Besok: :title', ['title' => $title]),
        );
    }

    /**
     * Point to the markdown view.
     */
    public function content(): Content
    {
        return new Content(markdown: 'mail.event-reminder');
    }
}
