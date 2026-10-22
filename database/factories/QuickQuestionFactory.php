<?php

namespace Database\Factories;

use App\Models\Module;
use App\Models\QuickQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuickQuestion>
 */
class QuickQuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'module_id' => Module::factory(),
            'question' => fake()->sentence().' ?',
            'answer' => fake()->boolean(),
            'explanation' => fake()->sentence(),
            'ordering' => fake()->numberBetween(1, 5),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the statement is true.
     */
    public function true(): static
    {
        return $this->state(fn (array $attributes): array => [
            'answer' => true,
        ]);
    }

    /**
     * Indicate that the statement is false.
     */
    public function false(): static
    {
        return $this->state(fn (array $attributes): array => [
            'answer' => false,
        ]);
    }
}
