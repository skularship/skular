<?php

namespace Database\Factories;

use App\Models\Fase;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Fase>
 */
class FaseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => 'Fase '.fake()->unique()->numberBetween(1, 999),
            'subtitle' => fake()->randomElement(['Fondasi', 'Pendalaman', 'Persiapan Pendaftaran']),
            'fase_order' => fake()->numberBetween(1, 10),
            'short_description' => fake()->sentence(),
        ];
    }
}
