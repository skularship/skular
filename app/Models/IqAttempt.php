<?php

namespace App\Models;

use App\Enums\IqAttemptStatus;
use Carbon\CarbonImmutable;
use Database\Factories\IqAttemptFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One time a mentee takes a Tes IQ: the question set frozen at start, the
 * server deadline, the question on screen (for time per question) and,
 * once closed, the server-computed scores. No IQ number is stored.
 *
 * @property int $id
 * @property int $user_id
 * @property int $iq_test_id
 * @property IqAttemptStatus $status
 * @property array<int, int> $question_ids Active question ids at start, in number order.
 * @property CarbonImmutable $started_at
 * @property CarbonImmutable $deadline_at
 * @property CarbonImmutable|null $completed_at
 * @property int|null $current_question_id Question on screen since current_viewed_at.
 * @property CarbonImmutable|null $current_viewed_at
 * @property int|null $answered_count Set when closed.
 * @property int|null $correct_count Set when closed.
 * @property int $total_questions
 * @property int|null $score_percent Rounded percent of correct answers.
 * @property string|null $category Category label from config/iq.php.
 * @property array<string, array{correct: int, total: int}>|null $subtest_scores Keyed by IqSubtest value.
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User $user
 * @property-read IqTest $test
 * @property-read Collection<int, IqAnswer> $answers
 */
#[Fillable([
    'user_id',
    'iq_test_id',
    'status',
    'question_ids',
    'started_at',
    'deadline_at',
    'completed_at',
    'current_question_id',
    'current_viewed_at',
    'answered_count',
    'correct_count',
    'total_questions',
    'score_percent',
    'category',
    'subtest_scores',
])]
class IqAttempt extends Model
{
    /** @use HasFactory<IqAttemptFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => IqAttemptStatus::class,
            'question_ids' => 'array',
            'started_at' => 'immutable_datetime',
            'deadline_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
            'current_viewed_at' => 'immutable_datetime',
            'answered_count' => 'integer',
            'correct_count' => 'integer',
            'total_questions' => 'integer',
            'score_percent' => 'integer',
            'subtest_scores' => 'array',
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
     * Get the test.
     *
     * @return BelongsTo<IqTest, $this>
     */
    public function test(): BelongsTo
    {
        return $this->belongsTo(IqTest::class, 'iq_test_id');
    }

    /**
     * Get the answer rows (one per viewed question).
     *
     * @return HasMany<IqAnswer, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(IqAnswer::class);
    }

    /**
     * Whether the given mentee owns this attempt.
     */
    public function isOwnedBy(?User $user): bool
    {
        return $user !== null && $this->user_id === $user->id;
    }

    /**
     * Whether the server deadline plus grace has passed.
     */
    public function isPastDeadline(): bool
    {
        return now()->greaterThan($this->deadline_at->addSeconds((int) config('iq.answer_grace_seconds', 5)));
    }
}
