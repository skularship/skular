<?php

namespace App\Models;

use App\Enums\AiFeedbackStatus;
use Carbon\CarbonImmutable;
use Database\Factories\TryoutAnswerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One served question of an attempt. The row is created when the question
 * is shown (response null, served_at) and filled when it is answered, which
 * is what makes a refresh resume on the same question. Writing tasks carry
 * their AI band; Speaking parts carry the transcript made in the browser.
 *
 * @property int $id
 * @property int $tryout_attempt_section_id
 * @property int $tryout_question_id
 * @property int $sequence Serving order 1..n.
 * @property int $level_at_answer
 * @property string|null $response Letter, text, or essay.
 * @property bool|null $is_correct Listening/Reading.
 * @property int|null $score
 * @property int|null $word_count
 * @property bool $over_word_limit
 * @property string|null $audio_path Unused: Speaking is transcribed in the browser and no audio is stored (kept for compatibility).
 * @property CarbonImmutable|null $audio_deleted_at Unused (see audio_path).
 * @property string|null $transcript Speaking: transcript made by the mentee's browser (Web Speech API).
 * @property AiFeedbackStatus|null $ai_status Writing assessment or Speaking transcription status.
 * @property array<string, mixed>|null $ai_assessment
 * @property string|null $ai_error
 * @property int $ai_attempts
 * @property float|null $band Writing task band.
 * @property CarbonImmutable|null $served_at
 * @property CarbonImmutable|null $answered_at
 * @property int|null $time_spent_seconds
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read TryoutAttemptSection $attemptSection
 * @property-read TryoutQuestion $question
 */
#[Fillable([
    'tryout_attempt_section_id',
    'tryout_question_id',
    'sequence',
    'level_at_answer',
    'response',
    'is_correct',
    'score',
    'word_count',
    'over_word_limit',
    'audio_path',
    'audio_deleted_at',
    'transcript',
    'ai_status',
    'ai_assessment',
    'ai_error',
    'ai_attempts',
    'band',
    'served_at',
    'answered_at',
    'time_spent_seconds',
])]
class TryoutAnswer extends Model
{
    /** @use HasFactory<TryoutAnswerFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'level_at_answer' => 'integer',
            'is_correct' => 'boolean',
            'score' => 'integer',
            'word_count' => 'integer',
            'over_word_limit' => 'boolean',
            'audio_deleted_at' => 'immutable_datetime',
            'ai_status' => AiFeedbackStatus::class,
            'ai_assessment' => 'array',
            'ai_attempts' => 'integer',
            'band' => 'float',
            'served_at' => 'immutable_datetime',
            'answered_at' => 'immutable_datetime',
            'time_spent_seconds' => 'integer',
        ];
    }

    /**
     * Get the subtest row of the attempt.
     *
     * @return BelongsTo<TryoutAttemptSection, $this>
     */
    public function attemptSection(): BelongsTo
    {
        return $this->belongsTo(TryoutAttemptSection::class, 'tryout_attempt_section_id');
    }

    /**
     * Get the question.
     *
     * @return BelongsTo<TryoutQuestion, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(TryoutQuestion::class, 'tryout_question_id');
    }

    /**
     * Whether the mentee already sent an answer.
     */
    public function isAnswered(): bool
    {
        return $this->answered_at !== null;
    }

    /**
     * Whether a running AI step stopped sending heartbeats (updated_at).
     */
    public function isStalled(): bool
    {
        return $this->ai_status === AiFeedbackStatus::Processing
            && ($this->updated_at === null || $this->updated_at->addSeconds(TryoutAttemptSection::STALLED_AFTER_SECONDS)->isPast());
    }

    /**
     * Whether "Nilai ulang" may run: failed or stalled, and under the retry cap.
     */
    public function canRetryAi(): bool
    {
        $failed_or_stalled = $this->ai_status === AiFeedbackStatus::Failed || $this->isStalled();

        return $failed_or_stalled && $this->ai_attempts <= (int) config('tryout.ai.max_retries_per_answer', 2);
    }
}
