<?php

namespace App\Models;

use App\Enums\ApplicationResult;
use App\Enums\ApplicationStatus;
use Carbon\CarbonImmutable;
use Database\Factories\ScholarshipApplicationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $scholarship_id
 * @property ApplicationStatus $status
 * @property bool $is_primary
 * @property CarbonImmutable|null $selected_at
 * @property CarbonImmutable|null $submitted_at
 * @property string|null $submission_proof
 * @property string|null $submission_note
 * @property CarbonImmutable|null $submission_confirmed_at
 * @property ApplicationResult|null $result
 * @property CarbonImmutable|null $result_reported_at
 * @property CarbonImmutable|null $learning_completed_at
 * @property array<string, mixed>|null $metadata
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Scholarship $scholarship
 * @property-read User $user
 */
#[Fillable([
    'user_id',
    'scholarship_id',
    'status',
    'is_primary',
    'selected_at',
    'submitted_at',
    'submission_proof',
    'submission_note',
    'submission_confirmed_at',
    'result',
    'result_reported_at',
    'learning_completed_at',
    'metadata',
])]
class ScholarshipApplication extends Model
{
    /** @use HasFactory<ScholarshipApplicationFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
            'result' => ApplicationResult::class,
            'is_primary' => 'boolean',
            'metadata' => 'array',
            'selected_at' => 'datetime',
            'submitted_at' => 'datetime',
            'submission_confirmed_at' => 'datetime',
            'result_reported_at' => 'datetime',
            'learning_completed_at' => 'datetime',
        ];
    }

    /**
     * Scope the query to the mentee's primary scholarship application.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function primary(Builder $query): void
    {
        $query->where('is_primary', true);
    }

    /**
     * Scope the query to applications whose learning path is still open.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function learningIncomplete(Builder $query): void
    {
        $query->whereNull('learning_completed_at');
    }

    /**
     * Determine whether the mentee has finished the learning path of this
     * scholarship and continued (or stopped) there.
     */
    public function hasCompletedLearning(): bool
    {
        return $this->learning_completed_at !== null;
    }

    /**
     * Determine whether a submission screenshot has been stored.
     */
    public function hasSubmissionProof(): bool
    {
        return filled($this->submission_proof);
    }

    /**
     * Determine whether the reported result qualifies for a twibbon.
     */
    public function qualifiesForTwibbon(): bool
    {
        return $this->result?->qualifiesForTwibbon() ?? false;
    }

    /**
     * Get the mentee that owns this application.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the scholarship being applied for.
     *
     * @return BelongsTo<Scholarship, $this>
     */
    public function scholarship(): BelongsTo
    {
        return $this->belongsTo(Scholarship::class);
    }
}
