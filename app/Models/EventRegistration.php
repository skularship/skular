<?php

namespace App\Models;

use App\Enums\EventRegistrationStatus;
use Carbon\CarbonImmutable;
use Database\Factories\EventRegistrationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One mentee's seat at an event, created only through EventService::register()
 * (unique per event and mentee).
 *
 * @property int $id
 * @property int $event_id
 * @property int $user_id
 * @property array<int, array{question: string, answer: string}>|null $answers Answers to the event's registration questions.
 * @property EventRegistrationStatus $status
 * @property array<string, string>|null $reminded_at Sent reminders, {"day"|"hour": ISO timestamp}.
 * @property int|null $feedback_score 1-5 after the event.
 * @property string|null $feedback_text
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Event $event
 * @property-read User $user
 */
#[Fillable(['event_id', 'user_id', 'answers', 'status', 'reminded_at', 'feedback_score', 'feedback_text'])]
class EventRegistration extends Model
{
    /** @use HasFactory<EventRegistrationFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'status' => EventRegistrationStatus::class,
            'reminded_at' => 'array',
            'feedback_score' => 'integer',
        ];
    }

    /**
     * Get the event.
     *
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get the mentee.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Whether the registration still holds a seat (not cancelled).
     */
    public function isActive(): bool
    {
        return $this->status !== EventRegistrationStatus::Cancelled;
    }

    /**
     * Whether a reminder of this kind ("day" or "hour") was already sent.
     */
    public function wasReminded(string $kind): bool
    {
        return isset(($this->reminded_at ?? [])[$kind]);
    }
}
