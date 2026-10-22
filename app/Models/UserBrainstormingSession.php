<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\UserBrainstormingSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The latest brainstorming answers and AI outline for one mentee and form.
 *
 * @property int $id
 * @property int $user_id
 * @property int $module_id
 * @property int $practice_form_id
 * @property array<string, mixed>|null $answers
 * @property array<string, mixed>|null $outline
 * @property string|null $summary
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'user_id',
    'module_id',
    'practice_form_id',
    'answers',
    'outline',
    'summary',
])]
class UserBrainstormingSession extends Model
{
    /** @use HasFactory<UserBrainstormingSessionFactory> */
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
            'outline' => 'array',
        ];
    }

    /**
     * Get the mentee that owns this session.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the module this session belongs to.
     *
     * @return BelongsTo<Module, $this>
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    /**
     * Get the practice form this session was created for.
     *
     * @return BelongsTo<PracticeForm, $this>
     */
    public function practiceForm(): BelongsTo
    {
        return $this->belongsTo(PracticeForm::class);
    }
}
