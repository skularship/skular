<?php

namespace App\Models;

use App\Enums\SupportTicketCategory;
use App\Enums\SupportTicketStatus;
use Carbon\CarbonImmutable;
use Database\Factories\SupportTicketFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A help request or bug report sent from the mentee "Bantuan" page.
 *
 * Created through SupportTicketService (rate limited, plain text only) and
 * handled by admins in the Filament "Tiket bantuan" resource. The optional
 * screenshot is stored on the private `local` disk.
 *
 * @property int $id
 * @property int|null $user_id Sender; null once the account is deleted.
 * @property SupportTicketCategory $category
 * @property string $subject
 * @property string $description
 * @property string|null $page_url Page where the problem happened.
 * @property string|null $screenshot_path Private screenshot on the local disk.
 * @property string|null $user_agent Browser string captured server-side for debugging.
 * @property SupportTicketStatus $status
 * @property int|null $handled_by Admin who last changed the status.
 * @property CarbonImmutable|null $resolved_at
 * @property string|null $admin_note Internal note, never shown to the mentee.
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User|null $user
 * @property-read User|null $handler
 */
#[Fillable([
    'user_id',
    'category',
    'subject',
    'description',
    'page_url',
    'screenshot_path',
    'user_agent',
    'status',
    'handled_by',
    'resolved_at',
    'admin_note',
])]
class SupportTicket extends Model
{
    /** @use HasFactory<SupportTicketFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => SupportTicketCategory::class,
            'status' => SupportTicketStatus::class,
            'resolved_at' => 'datetime',
        ];
    }

    /**
     * Get the mentee who sent the ticket.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin who last handled the ticket.
     *
     * @return BelongsTo<User, $this>
     */
    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}
