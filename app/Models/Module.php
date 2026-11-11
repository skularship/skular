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
use Illuminate\Database\Eloquent\Collection;
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
 * @property array<int, mixed>|null $mentor_suggestions
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read int|null $practice_forms_count
 * @property-read Fase $fase
 * @property-read Scholarship|null $scholarship
 * @property-read Collection<int, PracticeForm> $practiceForms
 * @property-read Collection<int, ModuleCoreSection> $coreSections
 * @property-read Collection<int, QuickQuestion> $quickQuestions
 * @property-read Collection<int, Checkpoint> $checkpoints
 * @property-read Collection<int, UserPracticeResponse> $practiceResponses
 * @property-read Collection<int, UserBrainstormingSession> $brainstormingSessions
 * @property-read Collection<int, ModuleFeedback> $feedbacks
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
    'mentor_suggestions',
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
            'mentor_suggestions' => 'array',
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

        // Checkpoints and practice responses cascade on delete, so removing a
        // module a mentee already worked on would erase their progress and
        // answers. Such modules are unpublished (draft) instead.
        static::deleting(function (Module $module): bool {
            return ! $module->hasMenteeHistory();
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
     * Maximum number of suggested questions shown in the AI Mentor chat.
     */
    public const MAX_MENTOR_SUGGESTIONS = 5;

    /**
     * Get the admin-written AI Mentor suggestions in display order.
     *
     * Blank questions are skipped and an empty answer becomes null, which
     * means the question is sent to the AI Mentor instead of answered directly.
     *
     * @return array<int, array{question: string, answer: string|null}>
     */
    public function mentorSuggestionList(): array
    {
        $suggestions = [];

        foreach (is_array($this->mentor_suggestions) ? $this->mentor_suggestions : [] as $suggestion) {
            if (! is_array($suggestion)) {
                continue;
            }

            $question = is_string($suggestion['question'] ?? null) ? trim($suggestion['question']) : '';

            if ($question === '') {
                continue;
            }

            $answer = is_string($suggestion['answer'] ?? null) ? trim($suggestion['answer']) : '';

            $suggestions[] = ['question' => $question, 'answer' => $answer === '' ? null : $answer];

            if (count($suggestions) >= self::MAX_MENTOR_SUGGESTIONS) {
                break;
            }
        }

        return $suggestions;
    }

    /**
     * Determine whether the module has a video the player can embed.
     */
    public function hasVideo(): bool
    {
        return $this->videoEmbedUrl() !== null;
    }

    /**
     * Get the iframe-ready URL of the module video.
     *
     * Admins may paste any YouTube link (watch, youtu.be, shorts, live or
     * embed); YouTube refuses to load a watch page inside an iframe, so every
     * form is rewritten to the privacy-enhanced embed player. Another valid
     * http(s) link is kept as-is for embeds from other hosts.
     *
     * @return string|null Null when the link is empty or unusable.
     */
    public function videoEmbedUrl(): ?string
    {
        $url = trim((string) $this->link_video);

        if ($url === '') {
            return null;
        }

        $youtube_id = self::youtubeVideoId($url);

        if ($youtube_id !== null) {
            return 'https://www.youtube-nocookie.com/embed/'.$youtube_id.'?rel=0';
        }

        if (self::isYoutubeHost(parse_url($url, PHP_URL_HOST))) {
            return null;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return filter_var($url, FILTER_VALIDATE_URL) !== false && in_array($scheme, ['http', 'https'], true)
            ? $url
            : null;
    }

    /**
     * Extract the 11-character video id from a YouTube link.
     *
     * Accepts youtube.com/watch?v=, youtu.be/, /embed/, /shorts/, /live/ and
     * /v/ links on youtube.com, m.youtube.com and youtube-nocookie.com.
     *
     * @param  string|null  $url  Link pasted by the admin.
     * @return string|null Null when the link is not a recognisable YouTube video.
     */
    public static function youtubeVideoId(?string $url): ?string
    {
        $url = trim((string) $url);

        if ($url !== '' && ! preg_match('#^https?://#i', $url)) {
            $url = 'https://'.$url;
        }

        $host = parse_url($url, PHP_URL_HOST);

        if (! self::isYoutubeHost($host)) {
            return null;
        }

        $host = strtolower((string) $host);
        $path = (string) parse_url($url, PHP_URL_PATH);
        $candidate = null;

        if ($host === 'youtu.be' || $host === 'www.youtu.be') {
            $candidate = explode('/', trim($path, '/'))[0];
        } elseif (preg_match('#^/(?:embed|shorts|live|v)/([^/?]+)#', $path, $matches) === 1) {
            $candidate = $matches[1];
        } elseif (rtrim($path, '/') === '/watch') {
            parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
            $candidate = is_string($query['v'] ?? null) ? $query['v'] : null;
        }

        return is_string($candidate) && preg_match('/^[A-Za-z0-9_-]{11}$/', $candidate) === 1
            ? $candidate
            : null;
    }

    /**
     * Determine whether a host belongs to YouTube.
     */
    private static function isYoutubeHost(mixed $host): bool
    {
        if (! is_string($host)) {
            return false;
        }

        return in_array(strtolower($host), [
            'youtube.com',
            'www.youtube.com',
            'm.youtube.com',
            'music.youtube.com',
            'youtube-nocookie.com',
            'www.youtube-nocookie.com',
            'youtu.be',
            'www.youtu.be',
        ], true);
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
     * Get the "Inti Materi" columns of this module in display order.
     *
     * @return HasMany<ModuleCoreSection, $this>
     */
    public function coreSections(): HasMany
    {
        return $this->hasMany(ModuleCoreSection::class)->orderBy('ordering')->orderBy('id');
    }

    /**
     * Get the filled "Inti Materi" columns, using the loaded relation when present.
     *
     * @return Collection<int, ModuleCoreSection>
     */
    public function filledCoreSections(): Collection
    {
        $sections = $this->relationLoaded('coreSections') ? $this->coreSections : $this->coreSections()->get();

        return $sections
            ->filter(fn (ModuleCoreSection $section): bool => filled($section->subtitle) && filled($section->body))
            ->sortBy([['ordering', 'asc'], ['id', 'asc']])
            ->values();
    }

    /**
     * Determine whether the module has any "Inti Materi" to show.
     *
     * Modules written before the column editor only have the legacy single
     * text, which still counts and is still shown.
     */
    public function hasCoreMaterial(): bool
    {
        return $this->filledCoreSections()->isNotEmpty() || filled($this->inti_material);
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
     * Determine whether any mentee progress or practice answer exists.
     *
     * Always asks the database so a delete never relies on a stale count.
     */
    public function hasMenteeHistory(): bool
    {
        return $this->checkpoints()->exists() || $this->practiceResponses()->exists();
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
