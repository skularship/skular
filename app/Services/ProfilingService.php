<?php

namespace App\Services;

use App\Concerns\ProfilingRules;
use App\Contracts\ProfilingServiceInterface;
use App\Enums\EducationLevel;
use App\Enums\LocationScope;
use App\Models\User;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class ProfilingService implements ProfilingServiceInterface
{
    use ProfilingRules;

    /**
     * The ordered profiling steps, matching the keys used in the session.
     *
     * @var array<int, string>
     */
    public const STEPS = [
        'level',
        'age',
        'gpa',
        'has_sktm',
        'desired_level',
        'desired_location',
        'cluster',
        'phone',
    ];

    /**
     * Get the ordered profiling steps.
     *
     * @return array<int, string>
     */
    public function steps(): array
    {
        return self::STEPS;
    }

    /**
     * Get the current value of every profiling step, falling back to persisted data.
     *
     * @return array<string, mixed>
     */
    public function values(User $user): array
    {
        $values = [];

        foreach (self::STEPS as $step) {
            $values[$step] = $this->value($user, $step);
        }

        return $values;
    }

    /**
     * Persist a single profiling step in the session.
     */
    public function put(string $step, mixed $value): void
    {
        Session::put($this->sessionKey($step), $value);
    }

    /**
     * Get the filled/completed progress of the profiling wizard.
     *
     * @return array{completed: int, total: int, percentage: int}
     */
    public function progress(User $user): array
    {
        $total = count(self::STEPS);
        $completed = 0;

        foreach (self::STEPS as $step) {
            if ($this->isFilled($this->value($user, $step))) {
                $completed++;
            }
        }

        return [
            'completed' => $completed,
            'total' => $total,
            'percentage' => (int) round($completed / $total * 100),
        ];
    }

    /**
     * Determine whether the profile holds enough data for scholarship matching.
     */
    public function isComplete(User $user): bool
    {
        if ($user->hasCompletedProfile()) {
            return true;
        }

        $progress = $this->progress($user);

        return $progress['completed'] === $progress['total'];
    }

    /**
     * Persist every profiling step onto the mentee and clear the session draft.
     */
    public function complete(User $user): User
    {
        $values = $this->values($user);

        $validated = Validator::make($values, $this->profilingRules(), $this->profilingMessages())
            ->validate();

        $user->forceFill([
            'level' => EducationLevel::from((string) $validated['level']),
            'age' => (int) $validated['age'],
            'gpa' => (float) $validated['gpa'],
            'has_sktm' => (bool) $validated['has_sktm'],
            'desired_level' => EducationLevel::from((string) $validated['desired_level']),
            'desired_location' => LocationScope::from((string) $validated['desired_location']),
            'cluster_id' => (int) $validated['cluster'],
            'phone' => (string) $validated['phone'],
            'profile_completed_at' => now(),
        ])->save();

        $this->forget();

        return $user;
    }

    /**
     * Discard the session draft.
     */
    public function forget(): void
    {
        foreach (self::STEPS as $step) {
            Session::forget($this->sessionKey($step));
        }
    }

    /**
     * Get the friendly validation messages for the profiling wizard.
     *
     * @return array<string, string>
     */
    private function profilingMessages(): array
    {
        return [
            'phone.regex' => 'Nomor telepon hanya boleh berisi angka, tanda +, tanda -, dan spasi.',
            'cluster.exists' => 'Bidang minat yang dipilih tidak tersedia. Silakan pilih ulang.',
        ];
    }

    /**
     * Resolve the current value of a profiling step, preferring the session draft.
     */
    private function value(User $user, string $step): mixed
    {
        $session_value = Session::get($this->sessionKey($step));

        return $this->isFilled($session_value) ? $session_value : $this->persistedValue($user, $step);
    }

    /**
     * Get the value stored on the mentee record for a profiling step.
     */
    private function persistedValue(User $user, string $step): mixed
    {
        return match ($step) {
            'level' => $user->level?->value,
            'age' => $user->age,
            'gpa' => $user->gpa === null ? null : (float) $user->gpa,
            'has_sktm' => $user->has_sktm,
            'desired_level' => $user->desired_level?->value,
            'desired_location' => $user->desired_location?->value,
            'cluster' => $user->cluster_id,
            'phone' => $user->phone,
            default => null,
        };
    }

    /**
     * Determine whether a profiling value counts as answered.
     */
    private function isFilled(mixed $value): bool
    {
        return $value !== null && $value !== '';
    }

    /**
     * Get the session key holding a profiling step draft.
     */
    private function sessionKey(string $step): string
    {
        return 'profiling_'.$step;
    }
}
