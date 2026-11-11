<?php

namespace App\Contracts;

use App\Enums\SupportTicketStatus;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

/**
 * Receives help requests and bug reports from the "Bantuan" page, builds the
 * WhatsApp help link, and records admin handling of tickets.
 */
interface SupportTicketServiceInterface
{
    /**
     * Most tickets one mentee may send within 24 hours.
     */
    public const DAILY_LIMIT = 5;

    /**
     * Longest description, in characters.
     */
    public const MAX_DESCRIPTION_LENGTH = 2000;

    /**
     * Largest screenshot accepted, in kilobytes (5 MB).
     */
    public const MAX_SCREENSHOT_KB = 5120;

    /**
     * Private disk folder holding ticket screenshots.
     */
    public const SCREENSHOT_FOLDER = 'support-tickets';

    /**
     * Store a ticket after validating, sanitising and rate limiting it.
     *
     * At most DAILY_LIMIT tickets per mentee per 24 hours (enforced in the
     * backend). Text is stored as plain text; the screenshot goes to the
     * private `local` disk.
     *
     * @param  User  $user  The mentee asking for help.
     * @param  array<string, mixed>  $input  Keys: category, subject, description, page_url.
     * @param  UploadedFile|null  $screenshot  Optional, already validated image.
     * @param  string|null  $user_agent  Browser string from the request.
     * @return SupportTicket The stored ticket with status "new".
     *
     * @throws ValidationException When the input is invalid or the daily limit is reached.
     */
    public function submit(User $user, array $input, ?UploadedFile $screenshot = null, ?string $user_agent = null): SupportTicket;

    /**
     * Record an admin status change on a ticket.
     *
     * @param  SupportTicket  $ticket  The ticket being handled.
     * @param  User  $admin  The handling admin.
     * @param  SupportTicketStatus  $status  New status.
     * @param  string|null  $admin_note  Optional internal note; blank keeps the previous note.
     * @return SupportTicket The updated ticket.
     */
    public function updateStatus(SupportTicket $ticket, User $admin, SupportTicketStatus $status, ?string $admin_note = null): SupportTicket;

    /**
     * Build the wa.me link of the help line with a short prefilled message.
     *
     * @param  User|null  $user  Signed-in mentee, used to greet by name.
     * @return string|null The link, or null when no number is configured.
     */
    public function whatsappUrl(?User $user = null): ?string;

    /**
     * Format the configured help number for display (e.g. "+62 896-0488-3024").
     *
     * @return string|null Display number, or null when none is configured.
     */
    public function whatsappDisplayNumber(): ?string;
}
