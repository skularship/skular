<?php

namespace App\Models;

use App\Enums\ContentStatus;
use Carbon\CarbonImmutable;
use Database\Factories\IqTestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

/**
 * One Tes IQ Latihan: its title, duration, question bank, the material
 * slides (PPT exported to PDF, read only in the viewer) and the mentor
 * notes the AI mentor reads. A test mentees already took cannot be deleted.
 *
 * @property int $id
 * @property string $code e.g. IQ-V01.
 * @property string $title
 * @property string|null $intro Short text on the start page.
 * @property int $duration_minutes
 * @property ContentStatus $status
 * @property string|null $material_pdf_path Private disk path; never downloadable.
 * @property string|null $material_pdf_name Title of the slides in the viewer.
 * @property CarbonImmutable|null $material_pdf_updated_at
 * @property string|null $mentor_notes Hidden; internal material text read by the AI mentor.
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Collection<int, IqQuestion> $questions
 * @property-read Collection<int, IqAttempt> $attempts
 */
#[Fillable([
    'code',
    'title',
    'intro',
    'duration_minutes',
    'status',
    'material_pdf_path',
    'material_pdf_name',
    'material_pdf_updated_at',
    'mentor_notes',
])]
#[Hidden(['material_pdf_path', 'mentor_notes'])]
class IqTest extends Model
{
    /** @use HasFactory<IqTestFactory> */
    use HasFactory;

    /**
     * Title of the slides when the admin left it empty.
     */
    public const DEFAULT_MATERIAL_NAME = 'Materi Tes IQ Skular';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'duration_minutes' => 'integer',
            'status' => ContentStatus::class,
            'material_pdf_updated_at' => 'immutable_datetime',
        ];
    }

    /**
     * Refuse to delete a test mentees already took (AGENTS rule 9) and stamp
     * the time the material PDF was replaced.
     */
    protected static function booted(): void
    {
        static::deleting(function (IqTest $test): void {
            if ($test->attempts()->exists()) {
                throw new LogicException('Tes IQ yang sudah dikerjakan mentee tidak bisa dihapus. Jadikan draf saja.');
            }
        });

        static::saving(function (IqTest $test): void {
            if ($test->isDirty('material_pdf_path')) {
                $test->material_pdf_updated_at = $test->material_pdf_path === null ? null : now();
            }
        });
    }

    /**
     * Get the questions in number order.
     *
     * @return HasMany<IqQuestion, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(IqQuestion::class)->orderBy('number');
    }

    /**
     * Get the attempts of this test.
     *
     * @return HasMany<IqAttempt, $this>
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(IqAttempt::class);
    }

    /**
     * Only tests visible to mentees.
     *
     * @param  Builder<IqTest>  $query
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('status', ContentStatus::Published->value);
    }

    /**
     * Whether material slides were uploaded.
     */
    public function hasMaterialPdf(): bool
    {
        return filled($this->material_pdf_path);
    }

    /**
     * Title of the slides in the viewer.
     */
    public function materialTitle(): string
    {
        return filled($this->material_pdf_name) ? (string) $this->material_pdf_name : self::DEFAULT_MATERIAL_NAME;
    }
}
