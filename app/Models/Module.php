<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Enums\ModuleCategory;
use App\Enums\ModuleType;
use App\Services\BrainstormingConfigService;
use App\Services\ContentCache;
use Carbon\CarbonImmutable;
use Database\Factories\ModuleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $fase_id
 * @property string $title
 * @property int $modul_order
 * @property string|null $summary_material
 * @property string|null $introduction
 * @property array<int, string> $insights
 * @property string|null $inti_material
 * @property ModuleType $type
 * @property ContentStatus $content_status
 * @property ModuleCategory $category
 * @property int|null $scholarship_id
 * @property int|null $duration
 * @property string|null $goal
 * @property string|null $link_video
 * @property string|null $ai_knowledge
 * @property array<int, array<string, mixed>>|null $brainstorming_questions
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read int|null $practice_forms_count
 * @property-read Fase $fase
 */
#[Fillable([
    'fase_id',
    'title',
    'modul_order',
    'summary_material',
    'introduction',
    'insights',
    'inti_material',
    'type',
    'content_status',
    'category',
    'scholarship_id',
    'duration',
    'goal',
    'link_video',
    'ai_knowledge',
    'brainstorming_questions',
])]
class Module extends Model
{
    /** @use HasFactory<ModuleFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'insights' => 'array',
            'brainstorming_questions' => 'array',
            'type' => ModuleType::class,
            'content_status' => ContentStatus::class,
            'category' => ModuleCategory::class,
            'modul_order' => 'integer',
            'duration' => 'integer',
        ];
    }

    /**
     * Keep the scholarship relationship in sync with the module category.
     *
     * A scholarship only makes sense for template modules, so switching a
     * module back to Inti or Perpustakaan clears the stale relation instead of
     * leaving the record in a contradictory state.
     */
    protected static function booted(): void
    {
        static::saving(function (Module $module): void {
            // Normalise brainstorming data on every write path, not only the
            // Filament editor, so stored identifiers stay stable and readable.
            if (array_key_exists('brainstorming_questions', $module->getDirty()) && is_array($module->brainstorming_questions)) {
                $module->brainstorming_questions = app(BrainstormingConfigService::class)
                    ->normaliseForStorage($module->brainstorming_questions);
            }

            // An unset category falls back to the database default (Inti), which
            // never belongs to a single scholarship.
            $category = $module->category ?? ModuleCategory::Inti;

            if ($category->requiresScholarship()) {
                return;
            }

            $module->scholarship_id = null;
        });

        // Curriculum reads are cached per content version; any edit invalidates
        // them so mentees never see a stale roadmap.
        static::saved(function (): void {
            ContentCache::bump();
        });

        static::deleted(function (): void {
            ContentCache::bump();
        });
    }

    /**
     * Scope the query to modules in curriculum order.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('fase_id')->orderBy('modul_order')->orderBy('id');
    }

    /**
     * Scope the query to the modules mentees are allowed to see.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('content_status', ContentStatus::Published->value);
    }

    /**
     * Scope the query to the modules the curriculum team is still working on.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function draft(Builder $query): void
    {
        $query->where('content_status', ContentStatus::Draft->value);
    }

    /**
     * Determine whether the module is part of the mentee learning experience.
     */
    public function isPublished(): bool
    {
        return $this->content_status->isPublished();
    }

    /**
     * Determine whether the module is still being written.
     */
    public function isDraft(): bool
    {
        return ! $this->isPublished();
    }

    /**
     * @return Attribute<array<int, string>, array<int, string>>
     *
     * Always expose the important points as a list of strings.
     *
     * Records written before the list editor existed stored the points as one
     * plain text value with a point per line. Resolving them here keeps the
     * mentee view, the curriculum editor and the readiness checks working on
     * the same shape instead of silently dropping old content.
     */
    protected function insights(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value): array {
                if (is_string($value)) {
                    // The stored value is JSON: either a list of points, or a
                    // single text block holding one point per line.
                    $value = json_decode($value, true) ?? $value;
                }

                if (is_string($value)) {
                    $lines = preg_split('/\r\n|\r|\n/', $value) ?: [];

                    return array_values(array_filter($lines, fn (string $line): bool => trim($line) !== ''));
                }

                return is_array($value) ? array_values($value) : [];
            },
        );
    }

    /**
     * Determine whether the module has a video to play.
     */
    public function hasVideo(): bool
    {
        return filled($this->link_video);
    }

    /**
     * Determine whether the module is bound to a single scholarship.
     */
    public function isTemplate(): bool
    {
        return $this->category === ModuleCategory::Template;
    }

    /**
     * Determine whether the module belongs to the scholarship roadmap.
     */
    public function isOnRoadmap(): bool
    {
        return $this->category !== ModuleCategory::Perpustakaan;
    }

    /**
     * Scope the query to modules of the given curriculum category.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function category(Builder $query, ModuleCategory $category): void
    {
        $query->where('category', $category->value);
    }

    /**
     * Get the scholarship a template module is tailored to.
     *
     * @return BelongsTo<Scholarship, $this>
     */
    public function scholarship(): BelongsTo
    {
        return $this->belongsTo(Scholarship::class);
    }

    /**
     * Get the fase this module belongs to.
     *
     * @return BelongsTo<Fase, $this>
     */
    public function fase(): BelongsTo
    {
        return $this->belongsTo(Fase::class);
    }

    /**
     * Get the practice forms configured for this module.
     *
     * @return HasMany<PracticeForm, $this>
     */
    public function practiceForms(): HasMany
    {
        return $this->hasMany(PracticeForm::class)->orderBy('ordering')->orderBy('id');
    }

    /**
     * Get the quick questions configured for this module.
     *
     * @return HasMany<QuickQuestion, $this>
     */
    public function quickQuestions(): HasMany
    {
        return $this->hasMany(QuickQuestion::class)->orderBy('ordering')->orderBy('id');
    }

    /**
     * Get the mentee checkpoints recorded for this module.
     *
     * @return HasMany<Checkpoint, $this>
     */
    public function checkpoints(): HasMany
    {
        return $this->hasMany(Checkpoint::class);
    }

    /**
     * Get the practice submissions recorded for this module.
     *
     * @return HasMany<UserPracticeResponse, $this>
     */
    public function practiceResponses(): HasMany
    {
        return $this->hasMany(UserPracticeResponse::class);
    }

    /**
     * Get the persisted brainstorming sessions for this module.
     *
     * @return HasMany<UserBrainstormingSession, $this>
     */
    public function brainstormingSessions(): HasMany
    {
        return $this->hasMany(UserBrainstormingSession::class);
    }

    /**
     * Get the feedback left by mentees for this module.
     *
     * @return HasMany<ModuleFeedback, $this>
     */
    public function feedbacks(): HasMany
    {
        return $this->hasMany(ModuleFeedback::class);
    }
}
