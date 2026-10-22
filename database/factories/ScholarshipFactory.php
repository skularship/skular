<?php

namespace Database\Factories;

use App\Enums\EducationLevel;
use App\Enums\LocationScope;
use App\Enums\ScholarshipStatus;
use App\Models\Scholarship;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Scholarship>
 */
class ScholarshipFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'scholarship_name' => 'Beasiswa '.fake()->unique()->company(),
            'institution' => fake()->company(),
            'level' => fake()->randomElement(EducationLevel::cases()),
            'location' => fake()->randomElement(['Indonesia', 'Jakarta', 'Yogyakarta', 'Bandung', 'Australia', 'Jepang', 'Belanda']),
            'scope' => LocationScope::DalamNegeri,
            'sktm_require' => false,
            'minimum_value' => null,
            'maximum_age' => null,
            'opening_date' => now()->subDays(30),
            'closing_date' => now()->addDays(30),
            'estimated_quota' => fake()->randomElement([null, 10, 25, 50, 100]),
            'link' => fake()->url(),
            'verification_date' => now()->addDays(fake()->numberBetween(130, 220)),
            'note' => fake()->sentence(),
            'status' => ScholarshipStatus::Active,
        ];
    }

    /**
     * Indicate that the scholarship is visible for the given education level.
     */
    public function forLevel(EducationLevel $level): static
    {
        return $this->state(fn (array $attributes): array => [
            'level' => $level,
        ]);
    }

    /**
     * Indicate that the scholarship requires an SKTM/KHI letter.
     */
    public function requiringSktm(): static
    {
        return $this->state(fn (array $attributes): array => [
            'sktm_require' => true,
        ]);
    }

    /**
     * Indicate that the scholarship has no eligibility restrictions.
     */
    public function withoutRequirements(): static
    {
        return $this->state(fn (array $attributes): array => [
            'minimum_value' => null,
            'maximum_age' => null,
            'sktm_require' => false,
        ]);
    }

    /**
     * Indicate that the scholarship is still a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ScholarshipStatus::Draft,
        ]);
    }

    /**
     * Indicate that the registration window has closed.
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ScholarshipStatus::Closed,
            'closing_date' => now()->subDays(5),
        ]);
    }

    /**
     * Indicate that the scholarship registration window ends soon.
     */
    public function closingSoon(int $days = 3): static
    {
        return $this->state(fn (array $attributes): array => [
            'closing_date' => now()->addDays($days),
        ]);
    }
}
