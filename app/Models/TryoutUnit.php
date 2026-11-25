<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Enums\TryoutExamType;
use App\Enums\TryoutSkill;
use Carbon\CarbonImmutable;
use Database\Factories\TryoutUnitFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * Shared content of the question bank: a Reading passage, a Listening audio
 * (with transcript, Pro/admin only), or a Writing/Speaking prompt group.
 * Reusable across Try Outs through tryout_section_unit.
 *
 * @property int $id
 * @property string $code e.g. LB-U1, RB-U3.
 * @property TryoutExamType $exam_type
 * @property TryoutSkill $skill
 * @property string $title
 * @property string|null $context e.g. "Staff perpustakaan dan mahasiswa".
 * @property string|null $passage Reading text, Markdown.
 * @property string|null $transcript Listening script; hidden, Pro/admin only.
 * @property string|null $audio_path mp3 on the public disk.
 * @property int|null $audio_duration_seconds
 * @property string|null $image_path Map or chart on the public disk.
 * @property array<string, array{x?: int, y?: int, label?: string}>|null $image_points
 * @property array{columns?: array<int, string>, rows?: array<int, array<int, string>>}|null $attachment Table shown under the audio.
 * @property ContentStatus $status
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read int|null $questions_count
 * @property-read Collection<int, TryoutQuestion> $questions
 * @property-read Collection<int, TryoutSection> $sections
 */
#[Fillable([
    'code',
    'exam_type',
    'skill',
    'title',
    'context',
    'passage',
    'transcript',
    'audio_path',
    'audio_duration_seconds',
    'image_path',
    'image_points',
    'attachment',
    'status',
])]
#[Hidden(['transcript'])]
class TryoutUnit extends Model
{
    /** @use HasFactory<TryoutUnitFactory> */
    use HasFactory;

    /**
     * Public disk and folder of the Listening audio and images.
     */
    public const MEDIA_DISK = 'public';

    public const AUDIO_DIRECTORY = 'tryout/audio';

    public const IMAGE_DIRECTORY = 'tryout/images';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'exam_type' => TryoutExamType::class,
            'skill' => TryoutSkill::class,
            'status' => ContentStatus::class,
            'audio_duration_seconds' => 'integer',
            'image_points' => 'array',
            'attachment' => 'array',
        ];
    }

    /**
     * Refuse to delete a unit whose questions were already answered.
     */
    protected static function booted(): void
    {
        static::deleting(function (TryoutUnit $unit): bool {
            return ! TryoutAnswer::query()->whereIn('tryout_question_id', $unit->questions()->select('id'))->exists();
        });
    }

    /**
     * Get the questions of this unit.
     *
     * @return HasMany<TryoutQuestion, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(TryoutQuestion::class)->orderBy('level')->orderBy('number')->orderBy('id');
    }

    /**
     * Get the subtests whose pool contains this unit.
     *
     * @return BelongsToMany<TryoutSection, $this>
     */
    public function sections(): BelongsToMany
    {
        return $this->belongsToMany(TryoutSection::class, 'tryout_section_unit')->withPivot('ordering')->withTimestamps();
    }

    /**
     * Public URL of the Listening audio, or null.
     */
    public function audioUrl(): ?string
    {
        return filled($this->audio_path) ? Storage::disk(self::MEDIA_DISK)->url((string) $this->audio_path) : null;
    }

    /**
     * Public URL of the map or chart image, or null.
     */
    public function imageUrl(): ?string
    {
        return filled($this->image_path) ? Storage::disk(self::MEDIA_DISK)->url((string) $this->image_path) : null;
    }

    /**
     * Average level of the published questions (for picking the next block).
     */
    public function averageLevel(): float
    {
        return (float) ($this->questions()->where('status', ContentStatus::Published->value)->avg('level') ?? 3);
    }
}
