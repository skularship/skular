<?php

namespace App\Models;

use App\Enums\TryoutAttemptStatus;
use App\Enums\TryoutMode;
use App\Enums\TryoutSkill;
use Carbon\CarbonImmutable;
use Database\Factories\TryoutAttemptFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * One time a mentee takes a Try Out: the chosen mode, the subtest in
 * progress and the overall band once every subtest is scored.
 *
 * @property int $id
 * @property int $user_id
 * @property int $tryout_id
 * @property TryoutMode $mode
 * @property TryoutAttemptStatus $status
 * @property TryoutSkill|null $current_skill
 * @property int $start_level
 * @property float|null $overall_band
 * @property CarbonImmutable|null $started_at
 * @property CarbonImmutable|null $completed_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User $user
 * @property-read Tryout $tryout
 * @property-read Collection<int, TryoutAttemptSection> $sections
 * @property-read Collection<int, TryoutAnswer> $answers
 */
#[Fillable([
    'user_id',
    'tryout_id',
    'mode',
    'status',
    'current_skill',
    'start_level',
    'overall_band',
    'started_at',
    'completed_at',
])]
class TryoutAttempt extends Model
{
    /** @use HasFactory<TryoutAttemptFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'mode' => TryoutMode::class,
            'status' => TryoutAttemptStatus::class,
            'current_skill' => TryoutSkill::class,
            'start_level' => 'integer',
            'overall_band' => 'float',
            'started_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
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
     * Get the Try Out.
     *
     * @return BelongsTo<Tryout, $this>
     */
    public function tryout(): BelongsTo
    {
        return $this->belongsTo(Tryout::class);
    }

    /**
     * Get the subtests of this attempt in the order they are taken.
     *
     * @return HasMany<TryoutAttemptSection, $this>
     */
    public function sections(): HasMany
    {
        return $this->hasMany(TryoutAttemptSection::class)->orderBy('id');
    }

    /**
     * Get every answer of this attempt.
     *
     * @return HasManyThrough<TryoutAnswer, TryoutAttemptSection, $this>
     */
    public function answers(): HasManyThrough
    {
        return $this->hasManyThrough(TryoutAnswer::class, TryoutAttemptSection::class);
    }

    /**
     * The attempt row of one subtest, or null.
     */
    public function section(TryoutSkill $skill): ?TryoutAttemptSection
    {
        return $this->sections->first(fn (TryoutAttemptSection $section): bool => $section->skill === $skill);
    }

    /**
     * Whether the given mentee owns this attempt.
     */
    public function isOwnedBy(?User $user): bool
    {
        return $user !== null && $this->user_id === $user->id;
    }

    /**
     * Whether the mentee finished every subtest.
     */
    public function isCompleted(): bool
    {
        return $this->status === TryoutAttemptStatus::Completed;
    }
}
