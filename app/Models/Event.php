<?php

namespace App\Models;

use App\Enums\EventAudience;
use App\Enums\EventRegistrationStatus;
use App\Enums\EventStatus;
use App\Enums\EventType;
use Carbon\CarbonImmutable;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use LogicException;

/**
 * A Skular event (webinar, intensive class, personal mentoring) that mentees
 * register for on the site instead of a Google Form. Registration, capacity,
 * audience and the link visibility rules live in EventService.
 *
 * @property int $id
 * @property string $slug
 * @property string $title
 * @property EventType $type
 * @property string|null $description Markdown, rendered with <x-learning.rich-content>.
 * @property string|null $image_path Poster on the public disk.
 * @property CarbonImmutable $starts_at Stored in UTC, shown in WIB.
 * @property CarbonImmutable $ends_at
 * @property string|null $location_url Zoom/Meet link; only for registered mentees from H-1.
 * @property int|null $capacity Null = unlimited.
 * @property EventAudience $audience
 * @property string|null $invite_tag users.tags value required when audience is Undangan.
 * @property array<int, string>|null $registration_questions Up to 3 short questions answered when registering.
 * @property EventStatus $status
 * @property string|null $recording_url Filled after the event.
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read string|null $image_url
 * @property-read int|null $seated_registrations_count
 * @property-read Collection<int, EventRegistration> $registrations
 */
#[Fillable([
    'slug',
    'title',
    'type',
    'description',
    'image_path',
    'starts_at',
    'ends_at',
    'location_url',
    'capacity',
    'audience',
    'invite_tag',
    'registration_questions',
    'status',
    'recording_url',
])]
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    /**
     * Disk and folder of the event posters.
     */
    public const IMAGE_DISK = 'public';

    public const IMAGE_DIRECTORY = 'events';

    /**
     * Maximum number of registration questions an admin may add.
     */
    public const MAX_QUESTIONS = 3;

    /**
     * Timezone the events are shown in.
     */
    public const DISPLAY_TIMEZONE = 'Asia/Jakarta';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => EventType::class,
            'audience' => EventAudience::class,
            'status' => EventStatus::class,
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
            'capacity' => 'integer',
            'registration_questions' => 'array',
        ];
    }

    /**
     * Refuse to delete an event that already has registrations; cancel it instead.
     */
    protected static function booted(): void
    {
        static::deleting(function (Event $event): void {
            if ($event->registrations()->exists()) {
                throw new LogicException('Event yang sudah punya pendaftar tidak bisa dihapus. Ubah statusnya menjadi Dibatalkan.');
            }
        });
    }

    /**
     * Event pages use the slug in the URL.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Get every registration of the event.
     *
     * @return HasMany<EventRegistration, $this>
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    /**
     * Limit a query to published events.
     *
     * @param  Builder<Event>  $query
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('status', EventStatus::Published);
    }

    /**
     * Count the registrations that hold a seat.
     *
     * @param  Builder<Event>  $query
     */
    #[Scope]
    protected function withSeatCount(Builder $query): void
    {
        $query->withCount(['registrations as seated_registrations_count' => fn (Builder $registrations) => $registrations
            ->whereIn('status', EventRegistrationStatus::holdingSeat())]);
    }

    /**
     * Get the public URL of the poster, or null.
     *
     * @return Attribute<string|null, never>
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => filled($this->image_path)
            ? Storage::disk(self::IMAGE_DISK)->url((string) $this->image_path)
            : null);
    }

    /**
     * Whether the event has already ended.
     */
    public function hasEnded(): bool
    {
        return $this->ends_at->isPast();
    }

    /**
     * Whether the event has started (registration closes then).
     */
    public function hasStarted(): bool
    {
        return $this->starts_at->isPast();
    }

    /**
     * Start time in WIB, e.g. "Sabtu, 22 November 2026 · 19.00 WIB".
     */
    public function startsAtLabel(): string
    {
        $start = $this->starts_at->setTimezone(self::DISPLAY_TIMEZONE);
        $end = $this->ends_at->setTimezone(self::DISPLAY_TIMEZONE);

        return $start->translatedFormat('l, j F Y').' · '.$start->format('H.i').'–'.$end->format('H.i').' WIB';
    }

    /**
     * The registration questions as a clean list (empty strings removed, at most three).
     *
     * @return array<int, string>
     */
    public function questions(): array
    {
        $questions = array_values(array_filter(
            array_map(trim(...), $this->registration_questions ?? []),
            fn (string $question): bool => $question !== '',
        ));

        return array_slice($questions, 0, self::MAX_QUESTIONS);
    }
}
