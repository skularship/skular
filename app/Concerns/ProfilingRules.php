<?php

namespace App\Concerns;

use App\Enums\EducationLevel;
use App\Enums\LocationScope;
use App\Models\Cluster;
use Illuminate\Validation\Rule;

trait ProfilingRules
{
    /**
     * Get the validation rules for a single profiling step or for the whole wizard.
     *
     * @return array<string, array<int, mixed>>
     */
    protected function profilingRules(?string $step = null): array
    {
        $rules = [
            'level' => ['required', Rule::enum(EducationLevel::class)],
            'age' => ['required', 'integer', 'min:15', 'max:60'],
            'gpa' => ['required', 'numeric', 'min:0', 'max:4'],
            'has_sktm' => ['required', 'boolean'],
            'desired_level' => ['required', Rule::enum(EducationLevel::class)],
            'desired_location' => ['required', Rule::enum(LocationScope::class)],
            'cluster' => [
                'required',
                'integer',
                Rule::exists(Cluster::class, 'id')->where('is_active', true),
            ],
            'phone' => ['required', 'string', 'min:10', 'max:15', 'regex:/^[0-9+\-\s]+$/'],
        ];

        if ($step === null) {
            return $rules;
        }

        return [$step => $rules[$step]];
    }

    /**
     * Get friendly attribute names for the profiling steps.
     *
     * @return array<string, string>
     */
    protected function profilingAttributes(): array
    {
        return [
            'level' => 'jenjang pendidikan',
            'age' => 'usia',
            'gpa' => 'IPK',
            'has_sktm' => 'status SKTM/KHI',
            'desired_level' => 'jenjang yang diinginkan',
            'desired_location' => 'lokasi tujuan',
            'cluster' => 'bidang minat',
            'phone' => 'nomor telepon',
        ];
    }
}
