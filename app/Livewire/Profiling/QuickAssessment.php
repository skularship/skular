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
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Profiling Kilat: a five question, login-free readiness check on the landing page.
 *
 * This is a marketing mini-assessment. It never touches the 8-step profiling
 * wizard, the mentee profile or the scholarship matching data.
 *
 * @property-read array<int, string> $steps
 * @property-read string $activeField
 * @property-read string $currentTitle
 * @property-read string $currentDescription
 * @property-read int $progressPercentage
 * @property-read array{title: string, description: string} $stepMeta
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

    public int $step = 1;

    public ?string $currentStatus = null;

    public ?string $target = null;

    public ?string $registerTimeline = null;

    /** @var array<int, string> */
    public array $assets = [];

    public ?string $weeklyPrepTime = null;

    public bool $submitted = false;

    /**
     * Run the primary action of the current question: advance or submit.
     */
    public function advance(): void
    {
        if ($this->step >= count(self::STEP_FIELDS)) {
            $this->submit();

            return;
        }

        $this->next();
    }

    /**
     * Validate the current question and move forward.
     */
    public function next(): void
    {
        $this->validate($this->stepRules($this->activeField), [], $this->quizAttributes());

        $this->resetErrorBag();

        if ($this->step < count(self::STEP_FIELDS)) {
            $this->moveToStep($this->step + 1);
        }
    }

    /**
     * Step back to the previous question, keeping every answer.
     */
    public function goBack(): void
    {
        $this->resetErrorBag();
        $this->moveToStep($this->step - 1);
    }

    /**
     * Point the mini wizard at another question.
     *
     * Computed properties are memoized for the whole request, and the question
     * currently on screen is read while validating the answer to the previous
     * one. Without dropping that memo, the render right after the action would
     * keep showing the question we just left.
     */
    protected function moveToStep(int $step): void
    {
        $this->step = max(1, min(count(self::STEP_FIELDS), $step));

        unset(
            $this->activeField,
            $this->stepMeta,
            $this->currentTitle,
            $this->currentDescription,
            $this->progressPercentage,
        );
    }

    /**
     * Pick or release a single-choice answer.
     */
    public function selectChoice(string $field, string $value): void
    {
        match ($field) {
            'currentStatus' => $this->currentStatus = $this->currentStatus === $value ? null : $value,
            'target' => $this->target = $this->target === $value ? null : $value,
            'registerTimeline' => $this->registerTimeline = $this->registerTimeline === $value ? null : $value,
            'weeklyPrepTime' => $this->weeklyPrepTime = $this->weeklyPrepTime === $value ? null : $value,
            default => null,
        };

        $this->resetErrorBag($field);
    }

    /**
     * Toggle a preparation asset, keeping "Belum Ada" exclusive.
     */
    public function toggleAsset(string $value): void
    {
        $asset = QuickProfilingAsset::tryFrom($value);

        if ($asset === null) {
            return;
        }

        $selected = array_values(array_filter(
            $this->assets,
            fn (string $item): bool => QuickProfilingAsset::tryFrom($item) instanceof QuickProfilingAsset,
        ));

        if ($asset->isExclusive()) {
            $this->assets = in_array($asset->value, $selected, true) ? [] : [$asset->value];
            $this->resetErrorBag('assets');

            return;
        }

        // Picking a real asset always releases the "Belum Ada" answer.
        $selected = array_values(array_diff($selected, [QuickProfilingAsset::None->value]));

        if (in_array($asset->value, $selected, true)) {
            $selected = array_values(array_diff($selected, [$asset->value]));
        } else {
            $selected[] = $asset->value;
        }

        $this->assets = $selected;
        $this->resetErrorBag('assets');
    }

    /**
     * Validate every answer, then reveal the result.
     */
    public function submit(): void
    {
        if ($this->submitted) {
            return;
        }

        $this->validate($this->quizRules(), [], $this->quizAttributes());

        $this->resetErrorBag();
        $this->submitted = true;
        $this->moveToStep(count(self::STEP_FIELDS));
    }

    /**
     * Clear every answer and return to the first question.
     */
    public function restart(): void
    {
        $this->reset('currentStatus', 'target', 'registerTimeline', 'assets', 'weeklyPrepTime', 'submitted');
        $this->moveToStep(1);
        $this->resetErrorBag();
    }

    /**
     * Get the ordered question field names.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function steps(): array
    {
        return array_values(self::STEP_FIELDS);
    }

    /**
     * Get the field backing the question currently displayed.
     */
    #[Computed]
    public function activeField(): string
    {
        return self::STEP_FIELDS[$this->step] ?? self::STEP_FIELDS[1];
    }

    /**
     * Get the copy of the active question.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function stepMeta(): array
    {
        $meta = self::STEP_META;

        return $meta[$this->activeField] ?? ['title' => '', 'description' => ''];
    }

    /**
     * Get the title of the active question.
     */
    #[Computed]
    public function currentTitle(): string
    {
        return $this->stepMeta['title'];
    }

    /**
     * Get the helper text of the active question.
     */
    #[Computed]
    public function currentDescription(): string
    {
        return $this->stepMeta['description'];
    }

    /**
     * Get the completion percentage of the mini wizard.
     */
    #[Computed]
    public function progressPercentage(): int
    {
        $total = count(self::STEP_FIELDS);

        return (int) round($this->step / $total * 100);
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

        $status = QuickProfilingStatus::tryFrom((string) $this->currentStatus);
        $goal = QuickProfilingGoal::tryFrom((string) $this->target);
        $timeline = QuickProfilingTimeline::tryFrom((string) $this->registerTimeline);
        $weekly = QuickProfilingWeeklyTime::tryFrom((string) $this->weeklyPrepTime);

        if ($status === null || $goal === null || $timeline === null || $weekly === null) {
            return null;
        }

        $assets = [];

        foreach ($this->assets as $asset) {
            $enum = QuickProfilingAsset::tryFrom($asset);

            if ($enum instanceof QuickProfilingAsset) {
                $assets[] = $enum;
            }
        }

        return app(ReadinessScoringServiceInterface::class)
            ->score($status, $goal, $timeline, $assets, $weekly);
    }

    public function render(): View
    {
        return view('livewire.profiling.quick-assessment');
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
     * Get the validation rules for a single question.
     *
     * @return array<string, array<int, mixed>>
     */
    protected function stepRules(string $field): array
    {
        $rules = $this->quizRules();

        return isset($rules[$field]) ? [$field => $rules[$field]] : [];
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
}
