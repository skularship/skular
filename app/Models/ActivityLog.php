<?php

namespace App\Models;

use App\Enums\ActivityName;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One tracked mentee action (behaviour log). Written only through
 * ActivityTrackerInterface; rows are never updated.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string|null $session_key SHA-256 of the session id, to group anonymous visits.
 * @property ActivityName $name
 * @property array<string, mixed>|null $properties
 * @property CarbonImmutable|null $created_at
 * @property-read User|null $user
 */
#[Fillable(['user_id', 'session_key', 'name', 'properties', 'created_at'])]
class ActivityLog extends Model
{
    /**
     * The log is append-only, so it has no updated_at column.
     */
    public const UPDATED_AT = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'name' => ActivityName::class,
            'properties' => 'array',
        ];
    }

    /**
     * Get the mentee who did the action, when signed in.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
