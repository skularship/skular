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
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * @property-read array<int, string> $steps
 * @property-read string $activeStep
 * @property-read string $currentLabel
 * @property-read Collection<int, Cluster> $clusters
 * @property-read array<string, string> $levelOptions
 * @property-read array<string, string> $locationOptions
 * @property-read array{completed: int, total: int, percentage: int} $progress
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

    #[Url(as: 'step')]
    public int $step = 1;

    public ?string $level = null;

    public ?string $age = null;

    public ?string $gpa = null;

    public ?bool $has_sktm = null;

    public ?string $desired_level = null;

    public ?string $desired_location = null;

    public ?string $cluster = null;

    public string $phone = '';

    /**
     * Hydrate the wizard from the session draft and persisted profile.
     */
    public function mount(ProfilingServiceInterface $profiling_service): void
    {
        $values = $profiling_service->values($this->user());

        $this->level = $this->stringValue($values['level'] ?? null);
        $this->age = $this->stringValue($values['age'] ?? null);
        $this->gpa = $this->stringValue($values['gpa'] ?? null);
        $this->has_sktm = is_bool($values['has_sktm'] ?? null) ? $values['has_sktm'] : null;
        $this->desired_level = $this->stringValue($values['desired_level'] ?? null);
        $this->desired_location = $this->stringValue($values['desired_location'] ?? null);
        $this->cluster = $this->stringValue($values['cluster'] ?? null);
        $this->phone = $this->stringValue($values['phone'] ?? null) ?? '';

        $this->step = max(1, min(count($profiling_service->steps()), $this->step));
    }

    /**
     * Validate the current step, store it in the session and advance.
     */
    public function next(ProfilingServiceInterface $profiling_service): void
    {
        $step = $this->activeStep;

        $this->validate($this->profilingRules($step), [], $this->profilingAttributes());

        $profiling_service->put($step, $this->valueFor($step));

        $this->resetErrorBag();

        if ($this->step < count($this->steps)) {
            $this->moveToStep($this->step + 1);
        }
    }

    /**
     * Advance to the recommendations when Enter is pressed on the last step.
     */
    public function submitStep(ProfilingServiceInterface $profiling_service): void
    {
        if ($this->step >= count($this->steps)) {
            $this->finish($profiling_service);

            return;
        }

        $this->next($profiling_service);
    }

    /**
     * Step back to the previous question.
     */
    public function goBack(): void
    {
        $this->resetErrorBag();
        $this->moveToStep($this->step - 1);
    }

    /**
     * Point the wizard at another step.
     *
     * Computed properties are memoized for the whole request, and the step on
     * screen is read while validating (and storing) the answer to the previous
     * one. Without dropping that memo, the render right after the action would
     * keep showing the step we just left.
     */
    protected function moveToStep(int $step): void
    {
        $this->step = max(1, min(count($this->steps), $step));

        unset($this->activeStep, $this->currentLabel);
    }

    /**
     * Persist the whole profile and continue to the recommendations.
     */
    public function finish(ProfilingServiceInterface $profiling_service): void
    {
        $steps = $this->steps;

        foreach ($steps as $step) {
            $profiling_service->put($step, $this->valueFor($step));
        }

        $this->validate($this->profilingRules($this->activeStep), [], $this->profilingAttributes());

        try {
            $profiling_service->complete($this->user());
        } catch (ValidationException $exception) {
            // Send the mentee to the first question that still needs an answer.
            $this->moveToStep($this->firstInvalidStep($exception, $steps));
            $this->resetErrorBag();

            throw $exception;
        }

        $this->redirect(route('profiling.recommendation'), navigate: true);
    }

    /**
     * Jump directly to a step that has already been answered.
     */
    public function goToStep(int $step): void
    {
        $this->resetErrorBag();
        $this->moveToStep($step);
    }

    /**
     * Select or release a choice on a selection card step.
     *
     * Every profiling card step holds a single answer, so choosing
     * another card releases the previous pick.
     */
    public function selectChoice(string $step, string $value): void
    {
        if (! in_array($step, ['level', 'has_sktm', 'desired_level', 'desired_location'], true)) {
            return;
        }

        if ($step === 'has_sktm') {
            $current = $this->has_sktm === true ? '1' : ($this->has_sktm === false ? '0' : null);
            $next = $current === $value ? null : $value;

            $this->has_sktm = $next === null ? null : $next === '1';
        } elseif ($step === 'level') {
            $this->level = (string) $this->level === $value ? null : $value;
        } elseif ($step === 'desired_level') {
            $this->desired_level = (string) $this->desired_level === $value ? null : $value;
        } elseif ($step === 'desired_location') {
            $this->desired_location = (string) $this->desired_location === $value ? null : $value;
        }

        $this->resetErrorBag($step);
    }

    /**
     * Select a cluster card, releasing the previous pick.
     */
    public function selectCluster(string $cluster_id): void
    {
        $this->cluster = (string) $this->cluster === $cluster_id ? null : $cluster_id;

        $this->resetErrorBag('cluster');
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
     * Get the active clusters offered during profiling.
     *
     * @return Collection<int, Cluster>
     */
    #[Computed]
    public function clusters()
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
     * Get the location scope options.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function locationOptions(): array
    {
        return LocationScope::options();
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
     * Get the completion progress of the wizard.
     *
     * @return array{completed: int, total: int, percentage: int}
     */
    #[Computed]
    public function progress(): array
    {
        return app(ProfilingServiceInterface::class)->progress($this->user());
    }

    public function render(): View
    {
        return view('livewire.profiling.wizard');
    }

    /**
     * Find the step index that holds the first validation error.
     *
     * @param  array<int, string>  $steps
     */
    private function firstInvalidStep(ValidationException $exception, array $steps): int
    {
        $errors = $exception->errors();

        foreach ($steps as $index => $step) {
            if (array_key_exists($step, $errors)) {
                return $index + 1;
            }
        }

        return 1;
    }

    /**
     * Read the wizard value backing a step.
     */
    private function valueFor(string $step): mixed
    {
        return match ($step) {
            'level' => $this->level,
            'age' => $this->age,
            'gpa' => $this->gpa,
            'has_sktm' => $this->has_sktm,
            'desired_level' => $this->desired_level,
            'desired_location' => $this->desired_location,
            'cluster' => $this->cluster,
            'phone' => $this->phone,
            default => null,
        };
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

    /**
     * Normalise a scalar session value into a nullable string.
     */
    private function stringValue(mixed $value): ?string
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (! is_scalar($value)) {
            return null;
        }

        return (string) $value;
    }
}
