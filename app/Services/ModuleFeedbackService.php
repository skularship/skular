<?php

namespace App\Services;

use App\Contracts\ModuleFeedbackServiceInterface;
use App\Enums\FeedbackDifficulty;
use App\Models\Module;
use App\Models\ModuleFeedback;
use App\Models\User;
use Closure;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Stores the mandatory end-of-module feedback a mentee sends in the checkpoint
 * step (one row per mentee per module) and the free-access commitment.
 *
 * The checkpoint cannot be completed without this feedback; the gate is
 * enforced in ModulePlayer and again in ProgressService::completeModule().
 */
class ModuleFeedbackService implements ModuleFeedbackServiceInterface
{
    /**
     * Minimum length of the written answers.
     */
    public const MIN_TEXT_LENGTH = 20;

    /**
     * Scores up to this value require a "why" answer.
     */
    public const LOW_SCORE_MAX = 3;

    /**
     * Rotating open questions, picked by modul_order % 3 when the module has no admin question.
     *
     * @var array<int, string>
     */
    public const OPEN_QUESTIONS = [
        'Di bagian mana kamu sempat bingung atau hampir berhenti?',
        'Apa satu hal yang paling berguna dari modul ini buat aplikasimu?',
        'Kalau bisa ubah satu hal di modul ini, apa?',
    ];

    /**
     * The checklist of the old feedback form, kept to read feedback sent before.
     *
     * @var array<int, string>
     */
    public const RATING_OPTIONS = [
        'Materi mudah dipahami',
        'Materi relevan',
        'AI feedback membantu',
        'Durasi sesuai',
        'Praktik membantu',
    ];

    /**
     * Validate and store the mentee's feedback for a module.
     *
     * @param  array{helpful_score?: mixed, difficulty?: mixed, open_answer?: mixed, low_score_reason?: mixed}  $input
     *
     * @throws ValidationException When a field is missing, too short, or only repeated characters.
     */
    public function submit(User $user, Module $module, array $input): ModuleFeedback
    {
        $input = [
            'helpful_score' => $input['helpful_score'] ?? null,
            'difficulty' => $input['difficulty'] ?? null,
            'open_answer' => is_string($input['open_answer'] ?? null) ? trim($input['open_answer']) : null,
            'low_score_reason' => is_string($input['low_score_reason'] ?? null) ? trim($input['low_score_reason']) : null,
        ];

        $is_low_score = is_numeric($input['helpful_score']) && (int) $input['helpful_score'] <= self::LOW_SCORE_MAX;

        $meaningful = function (string $attribute, mixed $value, Closure $fail): void {
            if (is_string($value) && $this->isJunk($value)) {
                $fail('Tulis jawaban yang sungguhan ya, bukan huruf atau tanda baca berulang.');
            }
        };

        /** @var array{helpful_score: int|string, difficulty: string, open_answer: string, low_score_reason?: string|null} $data */
        $data = Validator::make($input, [
            'helpful_score' => ['required', 'integer', 'between:1,5'],
            'difficulty' => ['required', Rule::enum(FeedbackDifficulty::class)],
            'open_answer' => ['required', 'string', 'min:'.self::MIN_TEXT_LENGTH, 'max:2000', $meaningful],
            'low_score_reason' => [
                Rule::requiredIf($is_low_score),
                'nullable',
                'string',
                'min:'.self::MIN_TEXT_LENGTH,
                'max:2000',
                $meaningful,
            ],
        ], [
            'helpful_score.required' => 'Pilih seberapa membantu modul ini (1 sampai 5).',
            'helpful_score.*' => 'Pilih angka 1 sampai 5.',
            'difficulty.required' => 'Pilih bagian yang paling menyulitkan (atau "Tidak ada").',
            'difficulty.*' => 'Pilih salah satu bagian yang tersedia.',
            'open_answer.required' => 'Jawab pertanyaan singkat ini dulu ya.',
            'open_answer.min' => 'Tulis minimal :min karakter supaya masukanmu jelas.',
            'low_score_reason.required' => 'Ceritakan apa yang bikin modul ini kurang membantu.',
            'low_score_reason.min' => 'Tulis minimal :min karakter supaya masukanmu jelas.',
        ])->validate();

        $score = (int) $data['helpful_score'];

        return ModuleFeedback::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'module_id' => $module->id,
            ],
            [
                'helpful_score' => $score,
                'difficulty' => FeedbackDifficulty::from($data['difficulty']),
                'open_question' => $this->openQuestionFor($module),
                'open_answer' => $data['open_answer'],
                'low_score_reason' => $score <= self::LOW_SCORE_MAX ? ($data['low_score_reason'] ?? null) : null,
            ],
        );
    }

    /**
     * Whether the mentee already sent the mandatory feedback for the module.
     */
    public function hasSubmitted(User $user, Module $module): bool
    {
        return ModuleFeedback::query()
            ->where('user_id', $user->id)
            ->where('module_id', $module->id)
            ->whereNotNull('helpful_score')
            ->exists();
    }

    /**
     * The open question shown for this module: the admin's question, or a rotating default.
     */
    public function openQuestionFor(Module $module): string
    {
        $custom = trim((string) $module->feedback_question);

        if ($custom !== '') {
            return $custom;
        }

        return self::OPEN_QUESTIONS[abs($module->modul_order) % count(self::OPEN_QUESTIONS)];
    }

    /**
     * Record that the mentee agreed to the free-access feedback commitment (idempotent).
     */
    public function acceptCommitment(User $user): void
    {
        if ($user->hasFeedbackCommitment()) {
            return;
        }

        $user->forceFill(['feedback_commitment_at' => now()])->save();
    }

    /**
     * Whether a text is only repeated characters, a repeated short pattern, or punctuation.
     *
     * Letters and digits are kept; "aaaaaaa", "asdasdasd", "hahahaha" and
     * "........" count as junk, a normal sentence never does.
     */
    private function isJunk(string $text): bool
    {
        $letters = (string) preg_replace('/[^\p{L}\p{N}]+/u', '', mb_strtolower($text));

        if (mb_strlen($letters) < 10) {
            return true;
        }

        if (count(array_unique(mb_str_split($letters))) <= 3) {
            return true;
        }

        return preg_match('/^(.{1,5})\1+$/u', $letters) === 1;
    }
}
