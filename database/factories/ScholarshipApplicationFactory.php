<?php

namespace Database\Factories;

use App\Enums\ApplicationResult;
use App\Enums\ApplicationStatus;
use App\Models\Scholarship;
use App\Models\ScholarshipApplication;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScholarshipApplication>
 */
class ScholarshipApplicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'scholarship_id' => Scholarship::factory(),
            'status' => ApplicationStatus::Selected,
            'is_primary' => false,
            'selected_at' => now(),
            'submitted_at' => null,
            'submission_proof' => null,
            'submission_note' => null,
            'submission_confirmed_at' => null,
            'result' => null,
            'result_reported_at' => null,
            'learning_completed_at' => null,
            'metadata' => null,
        ];
    }

    /**
     * Indicate that this is the mentee's primary scholarship.
     */
    public function primary(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_primary' => true,
        ]);
    }

    /**
     * Indicate that the mentee confirmed their external submission.
     */
    public function submitted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ApplicationStatus::Submitted,
            'submitted_at' => now(),
            'submission_proof' => 'submissions/proof.png',
            'submission_confirmed_at' => now(),
        ]);
    }

    /**
     * Indicate that the mentee finished the learning path of this scholarship.
     */
    public function learningCompleted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'learning_completed_at' => now(),
        ]);
    }

    /**
     * Indicate that the mentee reported the scholarship result.
     */
    public function reported(ApplicationResult $result): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ApplicationStatus::ResultReported,
            'submitted_at' => now()->subWeek(),
            'submission_proof' => 'submissions/proof.png',
            'submission_confirmed_at' => now()->subWeek(),
            'result' => $result,
            'result_reported_at' => now(),
        ]);
    }
}
