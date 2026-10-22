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
