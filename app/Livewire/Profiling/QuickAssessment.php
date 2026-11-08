<?php

namespace App\Livewire\Profiling;

use App\Contracts\ReadinessScoringServiceInterface;
use App\Data\ReadinessResultData;
use App\Enums\QuickProfilingAsset;
use App\Enums\QuickProfilingGoal;
use App\Enums\QuickProfilingStatus;
use App\Enums\QuickProfilingTimeline;
use App\Enums\QuickProfilingWeeklyTime;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Profiling Kilat: a five question, login-free readiness check on the landing page.
 *
 * This is a marketing mini-assessment. It never touches the 8-step profiling
 * wizard, the mentee profile or the scholarship matching data.
 *
 * Answers live in the browser (Alpine state, optionally restored from
 * sessionStorage) while the visitor walks the questions. Nothing is written
 * server-side until the visitor finishes and submit() receives the whole batch.
 *
 * @property-read array<int, array{field: string, title: string, description: string}> $questions
 * @property-read array<string, mixed> $initialAnswers
 * @property-read ReadinessResultData|null $result
 */
class QuickAssessment extends Component
{
    /**
     * Question order of the mini wizard.
     *
     * @var array<int, string>
     */
    public const STEP_FIELDS = [
        1 => 'currentStatus',
        2 => 'target',
        3 => 'registerTimeline',
        4 => 'assets',
        5 => 'weeklyPrepTime',
    ];

    /**
     * Copy shown for each question.
     *
     * @var array<string, array<string, string>>
     */
    public const STEP_META = [
        'currentStatus' => [
            'title' => 'Status kamu sekarang?',
            'description' => 'Kami pakai ini untuk menyesuaikan rekomendasi persiapanmu.',
        ],
        'target' => [
            'title' => 'Apa yang mau kamu kejar?',
            'description' => 'Pilih satu target utama yang paling ingin kamu capai.',
        ],
        'registerTimeline' => [
            'title' => 'Kapan target daftar?',
            'description' => 'Perkiraan kapan kamu akan mulai mendaftar.',
        ],
        'assets' => [
            'title' => 'Apa yang sudah kamu punya?',
            'description' => 'Boleh pilih lebih dari satu.',
        ],
        'weeklyPrepTime' => [
            'title' => 'Berapa waktu yang bisa kamu alokasikan setiap minggu?',
            'description' => 'Perkirakan secara realistis agar rencananya bisa kamu jalani.',
        ],
    ];

    /**
     * Browser storage key for an unfinished attempt.
     */
    public const STORAGE_KEY = 'skular.quick-assessment.v1';

    /**
     * The validated batch of answers. Empty until submit() accepts a batch.
     *
     * @var array<string, mixed>
     */
    public array $answers = [];

    public bool $submitted = false;

    /**
     * Validate the batch of answers collected by the browser, then score it.
     *
     * The frontend only ever calls this once, when the visitor presses the
     * final button. Nothing is trusted: every question and option is
     * re-validated here and the score is always recomputed server-side.
     *
     * @param  array<string, mixed>  $answers
     * @return array{ok: bool, step?: int, message?: string}
     */
    public function submit(array $answers): array
    {
        if ($this->submitted) {
            return ['ok' => true];
        }

        $validator = Validator::make($this->normalise($answers), $this->quizRules(), [], $this->quizAttributes());

        if ($validator->fails()) {
            $errors = $validator->errors();
            $this->setErrorBag($errors);

            return [
                'ok' => false,
                'step' => $this->firstInvalidStep($errors->toArray()),
                'message' => (string) $errors->first(),
            ];
        }

        $this->answers = $validator->validated();
        $this->submitted = true;
        $this->resetErrorBag();

        return ['ok' => true];
    }

    /**
     * Clear every answer and return to the first question.
     */
    public function restart(): void
    {
        $this->reset('answers', 'submitted');
        $this->resetErrorBag();
    }

    /**
     * Get the ordered questions rendered as one Alpine panel per question.
     *
     * @return array<int, array{field: string, title: string, description: string}>
     */
    #[Computed]
    public function questions(): array
    {
        $questions = [];

        foreach (self::STEP_FIELDS as $field) {
            $questions[] = [
                'field' => $field,
                'title' => self::STEP_META[$field]['title'],
                'description' => self::STEP_META[$field]['description'],
            ];
        }

        return $questions;
    }

    /**
     * Get the temporary answers the browser starts from.
     *
     * @return array<string, mixed>
     */
    #[Computed]
    public function initialAnswers(): array
    {
        return [
            'currentStatus' => $this->stringValue($this->answers['currentStatus'] ?? null),
            'target' => $this->stringValue($this->answers['target'] ?? null),
            'registerTimeline' => $this->stringValue($this->answers['registerTimeline'] ?? null),
            'assets' => $this->assetValues($this->answers['assets'] ?? []),
            'weeklyPrepTime' => $this->stringValue($this->answers['weeklyPrepTime'] ?? null),
        ];
    }

    /**
     * Score the submitted answers, or null while the form is still open.
     */
    #[Computed]
    public function result(): ?ReadinessResultData
    {
        if (! $this->submitted) {
            return null;
        }

        $status = QuickProfilingStatus::tryFrom((string) ($this->answers['currentStatus'] ?? ''));
        $goal = QuickProfilingGoal::tryFrom((string) ($this->answers['target'] ?? ''));
        $timeline = QuickProfilingTimeline::tryFrom((string) ($this->answers['registerTimeline'] ?? ''));
        $weekly = QuickProfilingWeeklyTime::tryFrom((string) ($this->answers['weeklyPrepTime'] ?? ''));

        if ($status === null || $goal === null || $timeline === null || $weekly === null) {
            return null;
        }

        $assets = [];

        foreach ($this->assetValues($this->answers['assets'] ?? []) as $asset) {
            $enum = QuickProfilingAsset::tryFrom($asset);

            if ($enum instanceof QuickProfilingAsset) {
                $assets[] = $enum;
            }
        }

        return app(ReadinessScoringServiceInterface::class)
            ->score($status, $goal, $timeline, $assets, $weekly);
    }

    /**
     * Render the public Profiling Kilat readiness quiz.
     *
     * @return View The livewire.profiling.quick-assessment view.
     */
    public function render(): View
    {
        return view('livewire.profiling.quick-assessment');
    }

    /**
     * Shape the submitted batch into the exact payload the rules expect.
     *
     * @param  array<string, mixed>  $answers
     * @return array<string, mixed>
     */
    protected function normalise(array $answers): array
    {
        $payload = [];

        foreach (array_values(self::STEP_FIELDS) as $field) {
            $value = $answers[$field] ?? null;

            if ($field === 'assets') {
                $payload[$field] = is_array($value) ? $this->assetValues($value) : null;

                continue;
            }

            $payload[$field] = is_scalar($value) ? trim((string) $value) : null;
        }

        return $payload;
    }

    /**
     * Keep the submitted asset values as a clean list of strings.
     *
     * Unknown values are kept so the enum rule rejects them instead of the
     * backend silently dropping a tampered answer.
     *
     * @return array<int, string>
     */
    protected function assetValues(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_unique(array_filter(
            $value,
            fn (mixed $item): bool => is_string($item) && $item !== '',
        )));
    }

    /**
     * Get the validation rules for the whole assessment.
     *
     * @return array<string, array<int, mixed>>
     */
    protected function quizRules(): array
    {
        return [
            'currentStatus' => ['required', Rule::enum(QuickProfilingStatus::class)],
            'target' => ['required', Rule::enum(QuickProfilingGoal::class)],
            'registerTimeline' => ['required', Rule::enum(QuickProfilingTimeline::class)],
            'assets' => ['required', 'array', 'min:1', $this->assetConflictRule()],
            'assets.*' => [Rule::enum(QuickProfilingAsset::class)],
            'weeklyPrepTime' => ['required', Rule::enum(QuickProfilingWeeklyTime::class)],
        ];
    }

    /**
     * Reject combining the exclusive "Belum Ada" answer with a real asset.
     */
    protected function assetConflictRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_array($value)) {
                return;
            }

            $others = array_values(array_diff($value, [QuickProfilingAsset::None->value]));

            if (in_array(QuickProfilingAsset::None->value, $value, true) && $others !== []) {
                $fail('Pilihan "Belum Ada" tidak bisa digabung dengan modal persiapan lain.');
            }
        };
    }

    /**
     * Get friendly attribute names for the validation messages.
     *
     * @return array<string, string>
     */
    protected function quizAttributes(): array
    {
        return [
            'currentStatus' => 'status saat ini',
            'target' => 'target yang dikejar',
            'registerTimeline' => 'waktu pendaftaran',
            'assets' => 'modal persiapan',
            'weeklyPrepTime' => 'waktu persiapan mingguan',
        ];
    }

    /**
     * Find the question number that holds the first validation error.
     *
     * @param  array<string, array<int, string>>  $errors
     */
    protected function firstInvalidStep(array $errors): int
    {
        $fields = array_values(self::STEP_FIELDS);

        foreach (array_keys($errors) as $key) {
            $field = explode('.', (string) $key)[0];
            $index = array_search($field, $fields, true);

            if ($index !== false) {
                return $index + 1;
            }
        }

        return 1;
    }

    /**
     * Normalise a scalar answer into a nullable string.
     */
    protected function stringValue(mixed $value): ?string
    {
        return is_scalar($value) && (string) $value !== '' ? (string) $value : null;
    }
}
