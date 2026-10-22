<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\QuickQuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $module_id
 * @property string $question
 * @property bool $answer
 * @property string|null $explanation
 * @property int $ordering
 * @property bool $is_active
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['module_id', 'question', 'answer', 'explanation', 'ordering', 'is_active'])]
class QuickQuestion extends Model
{
    /** @use HasFactory<QuickQuestionFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'answer' => 'boolean',
            'ordering' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Scope the query to active questions in display order.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function activeOrdered(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('ordering')->orderBy('id');
    }

    /**
     * Determine whether the given answer is correct.
     */
    public function isCorrect(?bool $given_answer): bool
    {
        return $given_answer !== null && $given_answer === $this->answer;
    }

    /**
     * Get the module this question belongs to.
     *
     * @return BelongsTo<Module, $this>
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }
}
