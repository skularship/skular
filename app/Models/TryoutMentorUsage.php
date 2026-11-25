<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * How many questions a mentee sent to the Try Out mentor for one attempt.
 *
 * @property int $id
 * @property int $user_id
 * @property int $tryout_attempt_id
 * @property int $used
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User $user
 * @property-read TryoutAttempt $attempt
 */
#[Fillable(['user_id', 'tryout_attempt_id', 'used'])]
class TryoutMentorUsage extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'used' => 'integer',
        ];
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
     * Get the attempt.
     *
     * @return BelongsTo<TryoutAttempt, $this>
     */
    public function attempt(): BelongsTo
    {
        return $this->belongsTo(TryoutAttempt::class, 'tryout_attempt_id');
    }
}
