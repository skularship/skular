<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Enums\ModuleCategory;
use App\Enums\ModuleType;
use App\Models\Fase;
use App\Models\Module;
use App\Models\Scholarship;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Module>
 */
class ModuleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'fase_id' => Fase::factory(),
            'title' => fake()->sentence(4),
            'modul_order' => fake()->numberBetween(1, 10),
            'summary_material' => fake()->paragraph(),
            'introduction' => fake()->paragraphs(2, true),
            'insights' => fake()->sentences(3),
            'inti_material' => fake()->paragraphs(3, true),
            'type' => ModuleType::Reading,
            'content_status' => ContentStatus::Published,
            'category' => ModuleCategory::Inti,
            'scholarship_id' => null,
            'duration' => fake()->numberBetween(15, 60),
            'goal' => fake()->sentence(),
            'link_video' => null,
            'ai_knowledge' => fake()->paragraphs(2, true),
            'brainstorming_questions' => null,
        ];
    }

    /**
     * Indicate that the module is still being written and hidden from mentees.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes): array => [
            'content_status' => ContentStatus::Draft,
        ]);
    }

    /**
     * Indicate that the module is a Template module for a scholarship.
     */
    public function template(Scholarship|int $scholarship): static
    {
        $scholarship_id = $scholarship instanceof Scholarship ? $scholarship->id : $scholarship;

        return $this->state(fn (array $attributes): array => [
            'category' => ModuleCategory::Template,
            'scholarship_id' => $scholarship_id,
        ]);
    }

    /**
     * Indicate that the module is a cross-scholarship Perpustakaan module.
     */
    public function library(): static
    {
        return $this->state(fn (array $attributes): array => [
            'category' => ModuleCategory::Perpustakaan,
            'scholarship_id' => null,
        ]);
    }

    /**
     * Indicate that the module includes a video.
     */
    public function withVideo(): static
    {
        return $this->state(fn (array $attributes): array => [
            'link_video' => 'https://www.youtube.com/watch?v='.fake()->regexify('[A-Za-z0-9_-]{11}'),
        ]);
    }
}
