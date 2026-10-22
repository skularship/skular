<?php

namespace Database\Factories;

use App\Models\Faq;
use App\Models\FaqCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Faq>
 */
class FaqFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'faq_category_id' => FaqCategory::factory(),
            'question' => rtrim(fake()->sentence(), '.').'?',
            'answer' => '<p>'.fake()->paragraph().'</p>',
            'is_published' => false,
            'is_featured' => false,
            'sort_order' => fake()->numberBetween(0, 20),
            'published_at' => null,
        ];
    }

    /**
     * Indicate that the FAQ is visible to the public.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_published' => true,
            'published_at' => now(),
        ]);
    }

    /**
     * Indicate that the FAQ is still a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_published' => false,
            'published_at' => null,
        ]);
    }

    /**
     * Indicate that the FAQ is prioritised for the landing page.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_featured' => true,
        ]);
    }
}
