<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Enums\TryoutExamType;
use Carbon\CarbonImmutable;
use Database\Factories\TryoutFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use LogicException;

/**
 * One Try Out package (e.g. "Try Out IELTS Academic 1"): its subtests come
 * from tryout_sections in `ordering` order. Also carries the three theory
 * layers of the Try Out (theory module, theory PDF, link to the paid material).
 *
 * @property int $id
 * @property string $code Stable code, e.g. TO1-AC. Never changes after publishing.
 * @property TryoutExamType $exam_type
 * @property string|null $variant e.g. "academic".
 * @property string $title
 * @property string $slug
 * @property string|null $description Markdown, shown on the intro page.
 * @property string|null $instructions General rules.
 * @property ContentStatus $status
 * @property bool $is_trial Shows the "Versi uji" label.
 * @property bool $requires_pro Only Pro mentees may start it.
 * @property int|null $theory_module_id Module with is_tryout_theory = true.
 * @property string|null $materials_url Link to the paid material (PDF) shop.
 * @property string|null $materials_label Button text of the materials link.
 * @property string|null $theory_pdf_path Theory PDF on the private local disk; only shown in the in-page viewer, never downloadable.
 * @property string|null $theory_pdf_name Title shown above the PDF viewer.
 * @property CarbonImmutable|null $theory_pdf_updated_at
 * @property int $ordering
 * @property CarbonImmutable|null $published_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read int|null $attempts_count
 * @property-read Collection<int, TryoutSection> $sections
 * @property-read Collection<int, TryoutAttempt> $attempts
 * @property-read Module|null $theoryModule
 */
#[Fillable([
    'code',
    'exam_type',
    'variant',
    'title',
    'slug',
    'description',
    'instructions',
    'status',
    'is_trial',
    'requires_pro',
    'theory_module_id',
    'materials_url',
    'materials_label',
    'theory_pdf_path',
    'theory_pdf_name',
    'theory_pdf_updated_at',
    'ordering',
    'published_at',
])]
class Tryout extends Model
{
    /** @use HasFactory<TryoutFactory> */
    use HasFactory;

    /**
     * Default text of the "buy the full material" button.
     */
    public const DEFAULT_MATERIALS_LABEL = 'Beli materi lengkap (PDF)';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'exam_type' => TryoutExamType::class,
            'status' => ContentStatus::class,
            'is_trial' => 'boolean',
            'requires_pro' => 'boolean',
            'ordering' => 'integer',
            'theory_pdf_updated_at' => 'immutable_datetime',
            'published_at' => 'immutable_datetime',
        ];
    }

    /**
     * Refuse to delete a Try Out that mentees already worked on (AGENTS rule 9).
     * Stamp published_at the first time the Try Out is published.
     */
    protected static function booted(): void
    {
        static::deleting(function (Tryout $tryout): void {
            if ($tryout->hasMenteeHistory()) {
                throw new LogicException('Try Out yang sudah dikerjakan mentee tidak bisa dihapus. Jadikan draf saja.');
            }
        });

        static::saving(function (Tryout $tryout): void {
            if ($tryout->status === ContentStatus::Published && $tryout->published_at === null) {
                $tryout->published_at = now();
            }
        });
    }

    /**
     * Try Out pages use the slug in the URL.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Get the subtests of this Try Out in the order they are taken.
     *
     * @return HasMany<TryoutSection, $this>
     */
    public function sections(): HasMany
    {
        return $this->hasMany(TryoutSection::class)->orderBy('ordering')->orderBy('id');
    }

    /**
     * Get every attempt of this Try Out.
     *
     * @return HasMany<TryoutAttempt, $this>
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(TryoutAttempt::class);
    }

    /**
     * Get the theory module read on the Try Out theory page.
     *
     * @return BelongsTo<Module, $this>
     */
    public function theoryModule(): BelongsTo
    {
        return $this->belongsTo(Module::class, 'theory_module_id');
    }

    /**
     * Limit a query to Try Outs visible to mentees.
     *
     * @param  Builder<Tryout>  $query
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('status', ContentStatus::Published->value);
    }

    /**
     * Whether a mentee already started this Try Out.
     */
    public function hasMenteeHistory(): bool
    {
        return $this->attempts()->exists();
    }

    /**
     * Whether a theory PDF is uploaded and still on the disk.
     */
    public function hasTheoryPdf(): bool
    {
        return filled($this->theory_pdf_path)
            && Storage::disk((string) config('tryout.theory_pdf.disk', 'local'))->exists((string) $this->theory_pdf_path);
    }

    /**
     * Whether a link to the paid material is set.
     */
    public function hasMaterialsLink(): bool
    {
        return filled($this->materials_url);
    }

    /**
     * Whether a published theory module is linked.
     */
    public function hasTheoryModule(): bool
    {
        return $this->theoryModule !== null && $this->theoryModule->is_tryout_theory;
    }

    /**
     * Text of the "buy the full material" button.
     */
    public function materialsLabel(): string
    {
        return filled($this->materials_label) ? (string) $this->materials_label : self::DEFAULT_MATERIALS_LABEL;
    }

    /**
     * Title shown above the theory PDF viewer.
     */
    public function theoryPdfTitle(): string
    {
        return filled($this->theory_pdf_name) ? (string) $this->theory_pdf_name : 'PDF teori '.$this->title;
    }

    /**
     * Total minutes of every subtest including transfer time.
     */
    public function totalMinutes(): int
    {
        $seconds = $this->sections->sum(fn (TryoutSection $section): int => $section->duration_seconds + $section->transfer_seconds);

        return (int) ceil($seconds / 60);
    }
}
