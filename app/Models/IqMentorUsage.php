<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * How many questions a mentee sent to the Tes IQ mentor on one day (Asia/Jakarta).
 *
 * @property int $id
 * @property int $user_id
 * @property CarbonImmutable $usage_date
 * @property int $used
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User $user
 */
#[Fillable(['user_id', 'usage_date', 'used'])]
class IqMentorUsage extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'usage_date' => 'immutable_date',
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
}
