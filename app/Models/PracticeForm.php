<?php

namespace App\Models;

use App\Services\ContentCache;
use Carbon\CarbonImmutable;
use Database\Factories\PracticeFormFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
 * @property string|null $cv_button_label Short text for the "Tampilkan ..." CV reference button; empty = form title.
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Module|null $module
 * @property-read Collection<int, UserPracticeResponse> $responses
 * @property-read Collection<int, UserBrainstormingSession> $brainstormingSessions
 * @property-read Collection<int, Module> $cvSourceFor
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
    'cv_button_label',
])]
class PracticeForm extends Model
{
    /** @use HasFactory<PracticeFormFactory> */
    use HasFactory;

    /**
     * Flush cached curriculum rows: they carry each module's
     * `practice_forms_count`, which changes with every form added or removed.
     */
    protected static function booted(): void
    {
        static::saved(function (): void {
            ContentCache::bump();
        });

        static::deleted(function (): void {
            ContentCache::bump();
        });
    }

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
     * Scope the query to forms that feed the mentee CV, in CV order.
     *
     * Only forms a mentee can still see count: the form must be active and its
     * module published, so a draft module or a hidden form never leaks into
     * the CV page or the CV reference panel of another module.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function cvSources(Builder $query): void
    {
        $query->where('practice_forms.include_in_cv', true)
            ->where('practice_forms.is_active', true)
            ->whereHas('module', fn (Builder $module): Builder => $module->published())
            ->orderBy('practice_forms.cv_ordering')
            ->orderBy('practice_forms.id');
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
     * Get the modules that show this form's answers as a CV reference.
     *
     * The pivot rows cascade when either side is deleted, so removing a form
     * or a module never leaves a dangling reference behind.
     *
     * @return BelongsToMany<Module, $this>
     */
    public function cvSourceFor(): BelongsToMany
    {
        return $this->belongsToMany(Module::class, 'module_cv_sources')->withTimestamps();
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
