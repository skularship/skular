<?php

namespace App\Mail;

use App\Models\ScholarshipApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email that tells a mentee a chosen scholarship closes in a few days.
 */
class DeadlineReminderMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    /**
     * Keep the application and the number of days left.
     */
    public function __construct(public ScholarshipApplication $application, public int $daysLeft)
    {
        $this->onQueue('reminders');
    }

    /**
     * Build the subject line.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->daysLeft === 1
                ? __('Besok terakhir: :name', ['name' => $this->application->scholarship->scholarship_name])
                : __(':n hari lagi: :name', ['n' => $this->daysLeft, 'name' => $this->application->scholarship->scholarship_name]),
        );
    }

    /**
     * Point to the markdown view.
     */
    public function content(): Content
    {
        return new Content(markdown: 'mail.deadline-reminder');
    }
}
