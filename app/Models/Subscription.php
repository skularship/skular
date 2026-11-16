<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The Pro access of one mentee. Pro is active while active_until is in the
 * future; when it passes, the mentee falls back to Gratis and keeps all data.
 *
 * @property int $id
 * @property int $user_id
 * @property string $plan Product code of the last purchase (pro_1bln or pro_12bln).
 * @property CarbonImmutable $active_until
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User $user
 */
#[Fillable(['user_id', 'plan', 'active_until'])]
class Subscription extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'active_until' => 'datetime',
        ];
    }

    /**
     * Get the mentee who owns this subscription.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
