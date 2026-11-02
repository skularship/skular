<?php

namespace App\Services;

use App\Concerns\ProfilingRules;
use App\Contracts\ProfilingServiceInterface;
use App\Enums\EducationLevel;
use App\Enums\LocationScope;
use App\Models\User;
use Illuminate\Support\Facades\DB;
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
     * Get the persisted value of every profiling step.
     *
     * @return array<string, mixed>
     */
    public function values(User $user): array
    {
        $values = [];

        foreach (self::STEPS as $step) {
            $values[$step] = $this->persistedValue($user, $step);
        }

        return $values;
    }

    /**
     * Get the filled/completed progress of the persisted profile.
     *
     * @return array{completed: int, total: int, percentage: int}
     */
    public function progress(User $user): array
    {
        $total = count(self::STEPS);
        $completed = 0;

        foreach (self::STEPS as $step) {
            if ($this->isFilled($this->persistedValue($user, $step))) {
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
     * Validate a whole profiling batch and persist it as one transaction.
     *
     * @param  array<string, mixed>  $answers
     */
    public function complete(User $user, array $answers): User
    {
        $validated = Validator::make($answers, $this->profilingRules(), $this->profilingMessages())
            ->validate();

        DB::transaction(function () use ($user, $validated): void {
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
        });

        return $user;
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
}
