<?php

namespace App\Models;

use App\Enums\AiFeedbackStatus;
use App\Enums\TryoutSectionStatus;
use App\Enums\TryoutSkill;
use Carbon\CarbonImmutable;
use Database\Factories\TryoutAttemptSectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One subtest inside an attempt: its server-side deadline, the adaptive
 * level, the active unit (block), the score and, for Speaking, the AI
 * assessment of the three parts.
 *
 * @property int $id
 * @property int $tryout_attempt_id
 * @property int $tryout_section_id Settings snapshot read from here.
 * @property TryoutSkill $skill
 * @property TryoutSectionStatus $status
 * @property CarbonImmutable|null $started_at
 * @property CarbonImmutable|null $deadline_at started_at + duration + transfer.
 * @property CarbonImmutable|null $submitted_at
 * @property int|null $current_level
 * @property int|null $final_level
 * @property int|null $current_unit_id Unit of the active block.
 * @property int|null $raw_score
 * @property int|null $max_score
 * @property float|null $band
 * @property AiFeedbackStatus|null $ai_status Speaking assessment status.
 * @property array<string, mixed>|null $ai_assessment
 * @property string|null $ai_error Admin only.
 * @property int $ai_attempts
 * @property CarbonImmutable|null $ai_heartbeat_at
 * @property array<string, int>|null $audio_plays Unit code => plays, counted by the server.
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read TryoutAttempt $attempt
 * @property-read TryoutSection $section
 * @property-read TryoutUnit|null $currentUnit
 * @property-read Collection<int, TryoutAnswer> $answers
 */
#[Fillable([
    'tryout_attempt_id',
    'tryout_section_id',
    'skill',
    'status',
    'started_at',
    'deadline_at',
    'submitted_at',
    'current_level',
    'final_level',
    'current_unit_id',
    'raw_score',
    'max_score',
    'band',
    'ai_status',
    'ai_assessment',
    'ai_error',
    'ai_attempts',
    'ai_heartbeat_at',
    'audio_plays',
])]
class TryoutAttemptSection extends Model
{
    /** @use HasFactory<TryoutAttemptSectionFactory> */
    use HasFactory;

    /**
     * Seconds without a heartbeat after which a running AI assessment counts
     * as stalled and offers "Nilai ulang" (same window as UserPracticeResponse).
     */
    public const STALLED_AFTER_SECONDS = 75;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'skill' => TryoutSkill::class,
            'status' => TryoutSectionStatus::class,
            'ai_status' => AiFeedbackStatus::class,
            'started_at' => 'immutable_datetime',
            'deadline_at' => 'immutable_datetime',
            'submitted_at' => 'immutable_datetime',
            'ai_heartbeat_at' => 'immutable_datetime',
            'current_level' => 'integer',
            'final_level' => 'integer',
            'raw_score' => 'integer',
            'max_score' => 'integer',
            'band' => 'float',
            'ai_assessment' => 'array',
            'ai_attempts' => 'integer',
            'audio_plays' => 'array',
        ];
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

    /**
     * Get the subtest settings.
     *
     * @return BelongsTo<TryoutSection, $this>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(TryoutSection::class, 'tryout_section_id');
    }

    /**
     * Get the unit of the active block.
     *
     * @return BelongsTo<TryoutUnit, $this>
     */
    public function currentUnit(): BelongsTo
    {
        return $this->belongsTo(TryoutUnit::class, 'current_unit_id');
    }

    /**
     * Get the served questions in serving order.
     *
     * @return HasMany<TryoutAnswer, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(TryoutAnswer::class)->orderBy('sequence');
    }

    /**
     * Seconds left before the deadline (0 when passed or not started).
     */
    public function remainingSeconds(): int
    {
        if ($this->deadline_at === null) {
            return 0;
        }

        return max(0, (int) now()->diffInSeconds($this->deadline_at, false));
    }

    /**
     * Whether the deadline plus the grace period has passed.
     */
    public function isExpired(): bool
    {
        if ($this->deadline_at === null) {
            return false;
        }

        return now()->greaterThan($this->deadline_at->addSeconds((int) config('tryout.answer_grace_seconds', 5)));
    }

    /**
     * Whether a running AI assessment stopped sending heartbeats.
     */
    public function isStalled(): bool
    {
        if ($this->ai_status !== AiFeedbackStatus::Processing) {
            return false;
        }

        $last = $this->ai_heartbeat_at ?? $this->updated_at;

        return $last === null || $last->addSeconds(self::STALLED_AFTER_SECONDS)->isPast();
    }

    /**
     * Times the audio of a unit was played in this subtest.
     */
    public function playsOf(string $unit_code): int
    {
        return (int) (($this->audio_plays ?? [])[$unit_code] ?? 0);
    }
}
