<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\IqAnswerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One question of an attempt as the mentee saw it: the latest choice (saved
 * on every pick), whether it matched the key at that moment, how often it
 * was changed and the seconds spent on it (internal data, not shown yet).
 * A row without choice is a question viewed but not answered.
 *
 * @property int $id
 * @property int $iq_attempt_id
 * @property int $iq_question_id
 * @property string|null $choice A, B, C or D.
 * @property bool|null $is_correct
 * @property CarbonImmutable|null $answered_at Time of the latest pick.
 * @property int $changes Picks after the first one.
 * @property int $time_spent_seconds
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read IqAttempt $attempt
 * @property-read IqQuestion $question
 */
#[Fillable([
    'iq_attempt_id',
    'iq_question_id',
    'choice',
    'is_correct',
    'answered_at',
    'changes',
    'time_spent_seconds',
])]
class IqAnswer extends Model
{
    /** @use HasFactory<IqAnswerFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
            'answered_at' => 'immutable_datetime',
            'changes' => 'integer',
            'time_spent_seconds' => 'integer',
        ];
    }

    /**
     * Get the attempt.
     *
     * @return BelongsTo<IqAttempt, $this>
     */
    public function attempt(): BelongsTo
    {
        return $this->belongsTo(IqAttempt::class, 'iq_attempt_id');
    }

    /**
     * Get the question.
     *
     * @return BelongsTo<IqQuestion, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(IqQuestion::class, 'iq_question_id');
    }
}
