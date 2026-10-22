<?php

namespace Database\Factories;

use App\Models\TwibbonTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TwibbonTemplate>
 */
class TwibbonTemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Template '.fake()->unique()->word(),
            'description' => fake()->sentence(),
            'image_path' => 'twibbon/templates/default.png',
            'font_path' => null,
            'placements' => TwibbonTemplate::DEFAULT_PLACEMENTS,
            'is_active' => true,
            'ordering' => 1,
        ];
    }

    /**
     * Indicate that the template is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
