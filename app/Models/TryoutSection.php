<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Enums\TryoutSkill;
use Carbon\CarbonImmutable;
use Database\Factories\TryoutSectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Settings of one subtest of a Try Out (duration, question count, adaptive
 * start level, audio replay limits) and the units that form its pool.
 *
 * @property int $id
 * @property int $tryout_id
 * @property TryoutSkill $skill
 * @property int $ordering
 * @property int $duration_seconds Admins enter minutes; stored in seconds.
 * @property int $transfer_seconds Extra review time after the main duration (Listening: 600).
 * @property int $question_count 40 for L/R, 2 for Writing, 3 for Speaking.
 * @property bool $is_adaptive
 * @property int $start_level
 * @property int|null $questions_per_unit Null = computed from the pool.
 * @property int $audio_plays_practice
 * @property int $audio_plays_exam
 * @property string|null $instructions
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Tryout $tryout
 * @property-read Collection<int, TryoutUnit> $units
 */
#[Fillable([
    'tryout_id',
    'skill',
    'ordering',
    'duration_seconds',
    'transfer_seconds',
    'question_count',
    'is_adaptive',
    'start_level',
    'questions_per_unit',
    'audio_plays_practice',
    'audio_plays_exam',
    'instructions',
])]
class TryoutSection extends Model
{
    /** @use HasFactory<TryoutSectionFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'skill' => TryoutSkill::class,
            'ordering' => 'integer',
            'duration_seconds' => 'integer',
            'transfer_seconds' => 'integer',
            'question_count' => 'integer',
            'is_adaptive' => 'boolean',
            'start_level' => 'integer',
            'questions_per_unit' => 'integer',
            'audio_plays_practice' => 'integer',
            'audio_plays_exam' => 'integer',
        ];
    }

    /**
     * Get the Try Out this subtest belongs to.
     *
     * @return BelongsTo<Tryout, $this>
     */
    public function tryout(): BelongsTo
    {
        return $this->belongsTo(Tryout::class);
    }

    /**
     * Get the units in the pool of this subtest, in pivot order.
     *
     * @return BelongsToMany<TryoutUnit, $this>
     */
    public function units(): BelongsToMany
    {
        return $this->belongsToMany(TryoutUnit::class, 'tryout_section_unit')
            ->withPivot('ordering')
            ->withTimestamps()
            ->orderByPivot('ordering')
            ->orderBy('tryout_units.id');
    }

    /**
     * Query of every published question in the published units of the pool.
     *
     * @return Builder<TryoutQuestion>
     */
    public function poolQuestions(): Builder
    {
        $unit_ids = $this->units()->where('tryout_units.status', ContentStatus::Published->value)->pluck('tryout_units.id');

        return TryoutQuestion::query()
            ->where('status', ContentStatus::Published->value)
            ->whereIn('tryout_unit_id', $unit_ids);
    }

    /**
     * Number of published pool questions per level (1..5).
     *
     * @return array<int, int>
     */
    public function poolCountByLevel(): array
    {
        $counts = array_fill(1, (int) config('tryout.adaptive.max_level', 5), 0);

        /** @var array<int, int> $rows */
        $rows = $this->poolQuestions()
            ->selectRaw('level, count(*) as aggregate')
            ->groupBy('level')
            ->pluck('aggregate', 'level')
            ->all();

        foreach ($rows as $level => $count) {
            $counts[(int) $level] = (int) $count;
        }

        return $counts;
    }

    /**
     * How many questions one unit (block) serves before the next unit starts.
     */
    public function perUnit(): int
    {
        if ($this->questions_per_unit !== null && $this->questions_per_unit > 0) {
            return $this->questions_per_unit;
        }

        $units = max(1, $this->units()->where('tryout_units.status', ContentStatus::Published->value)->count());

        return max(1, (int) ceil($this->question_count / $units));
    }

    /**
     * Total seconds the subtest runs (main duration plus transfer time).
     */
    public function totalSeconds(): int
    {
        return $this->duration_seconds + $this->transfer_seconds;
    }

    /**
     * Rounded duration in minutes, for cards ("30 + 10 menit").
     */
    public function durationLabel(): string
    {
        $minutes = (int) round($this->duration_seconds / 60);

        if ($this->transfer_seconds > 0) {
            return $minutes.' + '.((int) round($this->transfer_seconds / 60)).' menit';
        }

        return ($this->skill === TryoutSkill::Speaking ? 'sekitar ' : '').$minutes.' menit';
    }

    /**
     * Short summary of the subtest for cards ("40 soal, adaptif").
     */
    public function summaryLabel(): string
    {
        return match ($this->skill) {
            TryoutSkill::Writing => 'Task 1 dan 2',
            TryoutSkill::Speaking => 'Part 1, 2, 3',
            default => $this->question_count.' soal'.($this->is_adaptive ? ', adaptif' : ''),
        };
    }
}
