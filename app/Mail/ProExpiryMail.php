<?php

namespace App\Mail;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email that tells a mentee their Pro plan ends in a few days.
 */
class ProExpiryMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    /**
     * Keep the subscription and the number of days left.
     */
    public function __construct(public Subscription $subscription, public int $daysLeft)
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
                ? __('Pro kamu berakhir besok')
                : __('Pro kamu berakhir :n hari lagi', ['n' => $this->daysLeft]),
        );
    }

    /**
     * Point to the markdown view.
     */
    public function content(): Content
    {
        return new Content(markdown: 'mail.pro-expiry');
    }
}
