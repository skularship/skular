<?php

namespace App\Models;

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
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['user_id', 'module_id', 'ratings', 'suggestion'])]
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
