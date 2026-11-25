<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Enums\TryoutExamType;
use App\Enums\TryoutQuestionType;
use App\Enums\TryoutSkill;
use Carbon\CarbonImmutable;
use Database\Factories\TryoutQuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * One question of the Try Out bank. The key, accepted answers, outline and
 * explanation are hidden from serialization; mentee pages only ever receive
 * TryoutQuestionViewData, and the key only reaches Pro through TryoutReviewItemData.
 *
 * @property int $id
 * @property string $code e.g. LB-U1-01; never changes once answered.
 * @property int|null $tryout_unit_id Required for Listening/Reading.
 * @property TryoutExamType $exam_type
 * @property TryoutSkill $skill
 * @property int|null $number Number in the source document.
 * @property int|null $part Writing task 1/2, Speaking part 1/2/3.
 * @property int $level 1..5.
 * @property TryoutQuestionType $question_type
 * @property string|null $source_type Document label, e.g. "TFNG".
 * @property string $prompt
 * @property array<int, string>|null $prompt_items Speaking questions or cue card bullets.
 * @property array{columns?: array<int, string>, rows?: array<int, array<int, string>>}|null $prompt_table Writing Task 1 data table.
 * @property string|null $prompt_image_path Writing Task 1 chart on the public disk.
 * @property array<string, string>|null $options Letter => text.
 * @property string|null $answer_key Hidden.
 * @property array<int, string>|null $accepted_answers Hidden.
 * @property int|null $word_limit Short text: maximum words.
 * @property int|null $min_words Writing: 150 / 250.
 * @property int|null $prep_seconds Speaking Part 2: 60.
 * @property int|null $speak_seconds Speaking: maximum seconds per part.
 * @property int $max_score
 * @property string|null $rubric_key writing_v1, speaking_v1.
 * @property array<int, string>|null $answer_outline Hidden; guidance for the AI assessor.
 * @property string|null $explanation Hidden; Indonesian explanation for Pro.
 * @property array<int, string>|null $theory_tags
 * @property ContentStatus $status
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read TryoutUnit|null $unit
 */
#[Fillable([
    'code',
    'tryout_unit_id',
    'exam_type',
    'skill',
    'number',
    'part',
    'level',
    'question_type',
    'source_type',
    'prompt',
    'prompt_items',
    'prompt_table',
    'prompt_image_path',
    'options',
    'answer_key',
    'accepted_answers',
    'word_limit',
    'min_words',
    'prep_seconds',
    'speak_seconds',
    'max_score',
    'rubric_key',
    'answer_outline',
    'explanation',
    'theory_tags',
    'status',
])]
#[Hidden(['answer_key', 'accepted_answers', 'answer_outline', 'explanation'])]
class TryoutQuestion extends Model
{
    /** @use HasFactory<TryoutQuestionFactory> */
    use HasFactory;

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
            'question_type' => TryoutQuestionType::class,
            'status' => ContentStatus::class,
            'number' => 'integer',
            'part' => 'integer',
            'level' => 'integer',
            'prompt_items' => 'array',
            'prompt_table' => 'array',
            'options' => 'array',
            'accepted_answers' => 'array',
            'answer_outline' => 'array',
            'theory_tags' => 'array',
            'word_limit' => 'integer',
            'min_words' => 'integer',
            'prep_seconds' => 'integer',
            'speak_seconds' => 'integer',
            'max_score' => 'integer',
        ];
    }

    /**
     * Answered questions are archived (draft) instead of deleted (AGENTS rule 9).
     */
    protected static function booted(): void
    {
        static::deleting(function (TryoutQuestion $question): bool {
            return ! $question->hasAnswers();
        });

        // Options always leave the model as letter => text (A, B, C...), whatever
        // shape the admin form or the import sent (a plain list of texts).
        static::saving(function (TryoutQuestion $question): void {
            if (is_array($question->options)) {
                $question->options = self::letterOptions($question->options);
            }
        });
    }

    /**
     * Get the unit (passage/audio) this question belongs to.
     *
     * @return BelongsTo<TryoutUnit, $this>
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(TryoutUnit::class, 'tryout_unit_id');
    }

    /**
     * Get every answer given to this question.
     *
     * @return HasMany<TryoutAnswer, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(TryoutAnswer::class);
    }

    /**
     * Limit a query to published questions.
     *
     * @param  Builder<TryoutQuestion>  $query
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('status', ContentStatus::Published->value);
    }

    /**
     * Whether a mentee already answered this question (its code is then locked).
     */
    public function hasAnswers(): bool
    {
        return $this->answers()->exists();
    }

    /**
     * Options as letter => text, in letter order. Empty for non option types.
     *
     * @return array<string, string>
     */
    public function optionList(): array
    {
        $options = [];

        foreach ($this->options ?? [] as $letter => $text) {
            if (trim($text) !== '') {
                $options[strtoupper($letter)] = $text;
            }
        }

        ksort($options, SORT_NATURAL);

        return $options;
    }

    /**
     * Normalise options to letter => text: lettered A, B, C... in the given
     * order (the order the admin sees), so a removed option never leaves a gap
     * and the key picked in the form always matches. Empty texts are dropped.
     *
     * @param  array<array-key, mixed>  $options
     * @return array<string, string>
     */
    public static function letterOptions(array $options): array
    {
        $texts = [];

        foreach ($options as $key => $item) {
            if (is_array($item)) {
                $item = $item['text'] ?? reset($item);
            }

            if (is_scalar($item) && trim((string) $item) !== '') {
                $texts[$key] = trim((string) $item);
            }
        }

        $result = [];

        foreach (array_values($texts) as $index => $text) {
            $result[self::letter($index)] = $text;
        }

        return $result;
    }

    /**
     * Letter of the option at a 0-based position (A, B, C... up to Z).
     */
    public static function letter(int $index): string
    {
        return chr(65 + max(0, min($index, 25)));
    }

    /**
     * Level => label options (1..5) for admin selects and filters.
     *
     * @return array<int, string>
     */
    public static function levelOptions(): array
    {
        $options = [];

        foreach (range(1, (int) config('tryout.adaptive.max_level', 5)) as $level) {
            $options[$level] = (string) $level;
        }

        return $options;
    }

    /**
     * Public URL of the Writing Task 1 chart, or null.
     */
    public function promptImageUrl(): ?string
    {
        return filled($this->prompt_image_path) ? Storage::disk(TryoutUnit::MEDIA_DISK)->url((string) $this->prompt_image_path) : null;
    }
}
