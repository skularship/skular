<?php

namespace App\Models;

use App\Enums\FeedbackDifficulty;
use Carbon\CarbonImmutable;
use Database\Factories\ModuleFeedbackFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $module_id
 * @property array<int, string>|null $ratings
 * @property string|null $suggestion
 * @property int|null $helpful_score 1-5 "Seberapa membantu"; null on feedback from before the mandatory form.
 * @property FeedbackDifficulty|null $difficulty
 * @property string|null $open_question The open question as it was shown to the mentee.
 * @property string|null $open_answer
 * @property string|null $low_score_reason Required when helpful_score is 1-3.
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User $user
 * @property-read Module $module
 */
#[Fillable([
    'user_id',
    'module_id',
    'ratings',
    'suggestion',
    'helpful_score',
    'difficulty',
    'open_question',
    'open_answer',
    'low_score_reason',
])]
class ModuleFeedback extends Model
{
    /** @use HasFactory<ModuleFeedbackFactory> */
    use HasFactory;

    /**
     * The table for this model, which Eloquent cannot derive because
     * "feedback" is an uncountable word.
     */
    protected $table = 'module_feedbacks';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ratings' => 'array',
            'helpful_score' => 'integer',
            'difficulty' => FeedbackDifficulty::class,
        ];
    }

    /**
     * Get the mentee that submitted this feedback.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the module this feedback refers to.
     *
     * @return BelongsTo<Module, $this>
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }
}
