<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Enums\TryoutExamType;
use App\Enums\TryoutSkill;
use App\Models\TryoutUnit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TryoutUnit>
 */
class TryoutUnitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'U-'.Str::upper(Str::random(8)),
            'exam_type' => TryoutExamType::Ielts,
            'skill' => TryoutSkill::Reading,
            'title' => fake()->sentence(3),
            'context' => null,
            'passage' => fake()->paragraphs(3, true),
            'transcript' => 'RAHASIA-TRANSKRIP '.fake()->sentence(),
            'status' => ContentStatus::Published,
        ];
    }
}
