<?php

namespace App\Models;

use App\Enums\IqSubtest;
use Carbon\CarbonImmutable;
use Database\Factories\IqQuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * One multiple choice question (A-D) of a Tes IQ. The key and explanation
 * are hidden from serialization; the runner only ever receives
 * IqQuestionViewData and the key only reaches paying mentees through
 * IqReviewItemData. A question goes live (is_active) only when
 * activationErrors() is empty. Answered questions are never deleted.
 *
 * @property int $id
 * @property int $iq_test_id
 * @property int $number 1-based number shown to mentees.
 * @property IqSubtest $subtest
 * @property string|null $passage Reading text shown above the question (Teks).
 * @property string $prompt
 * @property string|null $image_path Public disk path (Visuospasial).
 * @property string|null $image_alt Alt text; required with an image.
 * @property string|null $option_a
 * @property string|null $option_b
 * @property string|null $option_c
 * @property string|null $option_d
 * @property string|null $answer_key Hidden; A, B, C or D.
 * @property string|null $explanation Hidden; for paying mentees.
 * @property bool $is_active
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read IqTest $test
 * @property-read Collection<int, IqAnswer> $answers
 */
#[Fillable([
    'iq_test_id',
    'number',
    'subtest',
    'passage',
    'prompt',
    'image_path',
    'image_alt',
    'option_a',
    'option_b',
    'option_c',
    'option_d',
    'answer_key',
    'explanation',
    'is_active',
])]
#[Hidden(['answer_key', 'explanation'])]
class IqQuestion extends Model
{
    /** @use HasFactory<IqQuestionFactory> */
    use HasFactory;

    /**
     * The option letters, in order.
     *
     * @var array<int, string>
     */
    public const LETTERS = ['A', 'B', 'C', 'D'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'subtest' => IqSubtest::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * Answered questions cannot be deleted (AGENTS rule 9); deactivate them
     * instead. As a last guard behind the admin form and the import, a
     * question that fails the activation rules is always saved inactive.
     */
    protected static function booted(): void
    {
        static::deleting(function (IqQuestion $question): bool {
            return ! $question->hasAnswers();
        });

        static::saving(function (IqQuestion $question): void {
            if ($question->answer_key !== null) {
                $question->answer_key = strtoupper(trim($question->answer_key));
            }

            if ($question->is_active && $question->activationErrors() !== []) {
                $question->is_active = false;
            }
        });
    }

    /**
     * Get the test.
     *
     * @return BelongsTo<IqTest, $this>
     */
    public function test(): BelongsTo
    {
        return $this->belongsTo(IqTest::class, 'iq_test_id');
    }

    /**
     * Get every answer given to this question.
     *
     * @return HasMany<IqAnswer, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(IqAnswer::class);
    }

    /**
     * Whether a mentee already saw or answered this question.
     */
    public function hasAnswers(): bool
    {
        return $this->answers()->exists();
    }

    /**
     * The four options as letter => text (an empty option stays '').
     *
     * @return array<string, string>
     */
    public function optionList(): array
    {
        return [
            'A' => trim((string) $this->option_a),
            'B' => trim((string) $this->option_b),
            'C' => trim((string) $this->option_c),
            'D' => trim((string) $this->option_d),
        ];
    }

    /**
     * Public URL of the image, or null.
     */
    public function imageUrl(): ?string
    {
        return filled($this->image_path)
            ? Storage::disk((string) config('iq.images.disk', 'public'))->url((string) $this->image_path)
            : null;
    }

    /**
     * Why this question cannot go live yet; empty when it can.
     *
     * @return array<int, string>
     */
    public function activationErrors(): array
    {
        $errors = [];

        if (trim((string) $this->prompt) === '') {
            $errors[] = 'Teks soal masih kosong.';
        }

        foreach ($this->optionList() as $letter => $text) {
            if ($text === '') {
                $errors[] = "Pilihan {$letter} masih kosong.";
            }
        }

        if (! in_array($this->answer_key, self::LETTERS, true)) {
            $errors[] = 'Kunci harus tepat satu huruf A, B, C, atau D.';
        }

        if (filled($this->image_path) && blank($this->image_alt)) {
            $errors[] = 'Teks alternatif gambar wajib diisi.';
        }

        if ($this->subtest->requiresImage() && blank($this->image_path)) {
            $errors[] = 'Soal Visuospasial wajib punya gambar.';
        }

        return $errors;
    }
}
