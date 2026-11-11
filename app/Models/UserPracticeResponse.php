<?php

namespace App\Models;

use App\Enums\AiFeedbackStatus;
use Carbon\CarbonImmutable;
use Database\Factories\UserPracticeResponseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $module_id
 * @property int $practice_form_id
 * @property array<string, mixed> $answers
 * @property array<string, mixed>|null $ai_feedback
 * @property int|null $ai_score
 * @property AiFeedbackStatus $ai_status
 * @property string|null $ai_error
 * @property int $attempts
 * @property CarbonImmutable|null $submitted_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User $user
 * @property-read Module $module
 * @property-read PracticeForm $practiceForm
 */
#[Fillable([
    'user_id',
    'module_id',
    'practice_form_id',
    'answers',
    'ai_feedback',
    'ai_score',
    'ai_status',
    'ai_error',
    'attempts',
    'submitted_at',
])]
class UserPracticeResponse extends Model
{
    /** @use HasFactory<UserPracticeResponseFactory> */
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
            'ai_feedback' => 'array',
            'ai_status' => AiFeedbackStatus::class,
            'ai_score' => 'integer',
            'attempts' => 'integer',
            'submitted_at' => 'datetime',
        ];
    }

    /**
     * Seconds without a heartbeat after which an unfinished evaluation is stalled.
     *
     * The synchronous evaluation touches updated_at when it starts and before
     * every provider attempt (at most 27 seconds apart: one agent timeout plus
     * the longest pause), so 75 seconds without a touch means the request
     * died midway (PHP or proxy timeout, a crashed process). Must stay at or
     * above PracticeSubmissionService::EVALUATION_LOCK_SECONDS, so the retry
     * offered at that point can always take the lock and start a new
     * evaluation.
     */
    public const STALLED_AFTER_SECONDS = 75;

    /**
     * Determine whether the AI evaluation is still expected to finish.
     */
    public function isEvaluating(): bool
    {
        return $this->isUnfinished() && ! $this->isStalled();
    }

    /**
     * Determine whether an unfinished evaluation stopped without an outcome.
     */
    public function isStalled(): bool
    {
        if (! $this->isUnfinished()) {
            return false;
        }

        $since = $this->updated_at ?? $this->submitted_at;

        return $since === null || $since->lt(now()->subSeconds(self::STALLED_AFTER_SECONDS));
    }

    /**
     * Determine whether the mentee may ask the AI to evaluate again.
     */
    public function canRetryEvaluation(): bool
    {
        return $this->ai_status === AiFeedbackStatus::Failed || $this->isStalled();
    }

    /**
     * Determine whether the evaluation has neither completed nor failed.
     */
    private function isUnfinished(): bool
    {
        return in_array($this->ai_status, [AiFeedbackStatus::Pending, AiFeedbackStatus::Processing], true);
    }

    /**
     * Determine whether the AI feedback is available to display.
     */
    public function hasFeedback(): bool
    {
        return $this->ai_status === AiFeedbackStatus::Completed && filled($this->ai_feedback);
    }

    /**
     * Get the mentee that submitted this practice.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the module this practice belongs to.
     *
     * @return BelongsTo<Module, $this>
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    /**
     * Get the practice form this response answered.
     *
     * @return BelongsTo<PracticeForm, $this>
     */
    public function practiceForm(): BelongsTo
    {
        return $this->belongsTo(PracticeForm::class);
    }
}
