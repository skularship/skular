<?php

namespace App\Livewire\Profiling;

use App\Concerns\ProfilingRules;
use App\Contracts\ProfilingServiceInterface;
use App\Enums\EducationLevel;
use App\Enums\LocationScope;
use App\Models\Cluster;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * The eight question profiling wizard of the onboarding flow.
 *
 * Answers are collected in the browser while the mentee walks the steps and
 * are only sent to the backend once, from submit(), at the end of the wizard.
 * Moving between steps never touches Livewire, the session or the database.
 *
 * @property-read array<int, string> $steps
 * @property-read array<string, string> $stepLabels
 * @property-read string $activeStep
 * @property-read string $currentLabel
 * @property-read Collection<int, Cluster> $clusters
 * @property-read array<string, string> $levelOptions
 * @property-read array<string, string> $locationOptions
 * @property-read array<string, mixed> $initialAnswers
 * @property-read string $storageKey
 */
#[Title('Profiling')]
class Wizard extends Component
{
    use ProfilingRules;

    /**
     * Human readable labels for each profiling step.
     *
     * @var array<string, string>
     */
    public const STEP_LABELS = [
        'level' => 'Jenjang',
        'age' => 'Usia',
        'gpa' => 'IPK',
        'has_sktm' => 'SKTM',
        'desired_level' => 'Target',
        'desired_location' => 'Lokasi',
        'cluster' => 'Bidang',
        'phone' => 'Telepon',
    ];

    /**
     * Base browser storage key for an unfinished wizard.
     */
    public const STORAGE_KEY = 'skular.profiling.wizard.v1';

    #[Url(as: 'step')]
    public int $step = 1;

    /**
     * Seed values for the temporary browser state.
     *
     * @var array<string, mixed>
     */
    public array $answers = [];

    /**
     * Hydrate the wizard from the persisted profile.
     */
    public function mount(ProfilingServiceInterface $profiling_service): void
    {
        $this->answers = $profiling_service->values($this->user());

        $this->step = max(1, min(count($profiling_service->steps()), $this->step));
    }

    /**
     * Validate and persist the whole profile from one batch submission.
     *
     * Called once, from the last step. Nothing is trusted: each answer is
     * re-validated against the backend rules and the profile is written in a
     * single transaction by the service. The redirect only happens after the
     * write succeeded, so a retry never produces a duplicate profile.
     *
     * @param  array<string, mixed>  $answers
     * @return array{ok: bool, step?: int, message?: string}
     */
    public function submit(array $answers, ProfilingServiceInterface $profiling_service): array
    {
        $steps = $profiling_service->steps();
        $payload = [];

        foreach ($steps as $step) {
            $payload[$step] = $this->normaliseValue($step, $answers[$step] ?? null);
        }

        $validator = Validator::make($payload, $this->profilingRules(), [], $this->profilingAttributes());

        if ($validator->fails()) {
            $errors = $validator->errors();
            $this->setErrorBag($errors);

            return [
                'ok' => false,
                'step' => $this->firstInvalidStep($errors->toArray(), $steps),
                'message' => (string) $errors->first(),
            ];
        }

        $this->answers = $payload;
        $this->resetErrorBag();

        $profiling_service->complete($this->user(), $payload);

        $this->redirect(route('profiling.recommendation'), navigate: true);

        return ['ok' => true];
    }

    /**
     * Get the ordered step keys.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function steps(): array
    {
        return app(ProfilingServiceInterface::class)->steps();
    }

    /**
     * Get the label of every step, keyed by step.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function stepLabels(): array
    {
        $labels = [];

        foreach ($this->steps as $step) {
            $labels[$step] = self::STEP_LABELS[$step] ?? '';
        }

        return $labels;
    }

    /**
     * Get the active clusters offered during profiling.
     *
     * @return Collection<int, Cluster>
     */
    #[Computed]
    public function clusters(): Collection
    {
        return Cluster::query()->activeOrdered()->get();
    }

    /**
     * Get the education level options.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function levelOptions(): array
    {
        return EducationLevel::options();
    }

    /**
     * Get the study destination options offered while profiling.
     *
     * Only domestic and international study remain here; funding categories
     * are a scholarship attribute and must never be a location answer.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function locationOptions(): array
    {
        return LocationScope::studyDestinationOptions();
    }

    /**
     * Get the temporary answers the browser starts from.
     *
     * @return array<string, mixed>
     */
    #[Computed]
    public function initialAnswers(): array
    {
        $answers = [];

        foreach ($this->steps as $step) {
            $value = $this->answers[$step] ?? null;

            $answers[$step] = match ($step) {
                'has_sktm' => $value === true ? '1' : ($value === false ? '0' : null),
                default => $value === null || $value === '' ? null : (string) $value,
            };
        }

        return $answers;
    }

    /**
     * Get the key of the step currently displayed.
     */
    #[Computed]
    public function activeStep(): string
    {
        $steps = $this->steps;

        return $steps[$this->step - 1] ?? ($steps[0] ?? 'level');
    }

    /**
     * Get the label of the current step.
     */
    #[Computed]
    public function currentLabel(): string
    {
        return self::STEP_LABELS[$this->activeStep] ?? '';
    }

    /**
     * Get the completion progress of the persisted profile.
     *
     * Only used for the first paint: the wizard repaints its progress from the
     * temporary answers in the browser, without asking the server.
     *
     * @return array{completed: int, total: int, percentage: int}
     */
    #[Computed]
    public function progress(): array
    {
        return app(ProfilingServiceInterface::class)->progress($this->user());
    }

    /**
     * Get the browser storage key of the signed-in mentee's draft.
     */
    #[Computed]
    public function storageKey(): string
    {
        return self::STORAGE_KEY.':'.$this->user()->getKey();
    }

    /**
     * Render the eight question profiling wizard (route profiling).
     *
     * @return View The livewire.profiling.wizard view.
     */
    public function render(): View
    {
        return view('livewire.profiling.wizard');
    }

    /**
     * Find the step index that holds the first validation error.
     *
     * @param  array<string, array<int, string>>  $errors
     * @param  array<int, string>  $steps
     */
    private function firstInvalidStep(array $errors, array $steps): int
    {
        foreach (array_keys($errors) as $key) {
            $step = explode('.', (string) $key)[0];
            $index = array_search($step, $steps, true);

            if ($index !== false) {
                return $index + 1;
            }
        }

        return 1;
    }

    /**
     * Shape one submitted answer into the type the validation rules expect.
     */
    private function normaliseValue(string $step, mixed $value): mixed
    {
        if ($step === 'has_sktm') {
            return match (true) {
                $value === true, $value === '1', $value === 1 => true,
                $value === false, $value === '0', $value === 0 => false,
                default => null,
            };
        }

        if (is_bool($value) || ! is_scalar($value)) {
            return null;
        }

        $normalised = trim((string) $value);

        return $normalised === '' ? null : $normalised;
    }

    /**
     * Get the authenticated mentee.
     */
    private function user(): User
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            abort(403);
        }

        return $user;
    }
}
