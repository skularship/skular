<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\PracticeFormFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $module_id
 * @property string $title
 * @property string|null $description
 * @property array<int, array<string, mixed>>|null $fields
 * @property int $ordering
 * @property bool $is_active
 * @property bool $include_in_cv
 * @property int $cv_ordering
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'module_id',
    'title',
    'description',
    'fields',
    'ordering',
    'is_active',
    'include_in_cv',
    'cv_ordering',
])]
class PracticeForm extends Model
{
    /** @use HasFactory<PracticeFormFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fields' => 'array',
            'ordering' => 'integer',
            'is_active' => 'boolean',
            'include_in_cv' => 'boolean',
            'cv_ordering' => 'integer',
        ];
    }

    /**
     * Scope the query to active forms in display order.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function activeOrdered(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('ordering')->orderBy('id');
    }

    /**
     * Scope the query to forms that feed the mentee CV.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function cvSources(Builder $query): void
    {
        $query->where('include_in_cv', true)->orderBy('cv_ordering')->orderBy('id');
    }

    /**
     * Get the module this form belongs to.
     *
     * @return BelongsTo<Module, $this>
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    /**
     * Get the mentee submissions for this form.
     *
     * @return HasMany<UserPracticeResponse, $this>
     */
    public function responses(): HasMany
    {
        return $this->hasMany(UserPracticeResponse::class);
    }

    /**
     * Get the persisted brainstorming sessions for this form.
     *
     * @return HasMany<UserBrainstormingSession, $this>
     */
    public function brainstormingSessions(): HasMany
    {
        return $this->hasMany(UserBrainstormingSession::class);
    }
}
