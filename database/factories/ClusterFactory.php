<?php

namespace Database\Factories;

use App\Models\Cluster;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Cluster>
 */
class ClusterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $label = fake()->unique()->randomElement([
            'Teknologi',
            'Bisnis',
            'Sosial',
            'Pendidikan',
            'Kesehatan',
            'Engineering',
            'Seni dan Desain',
            'Pertanian',
        ]);

        return [
            'name' => Str::slug($label),
            'label' => $label,
            'description' => fake()->sentence(),
            'icon' => null,
            'ordering' => fake()->numberBetween(1, 10),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the cluster is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
