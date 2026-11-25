<?php

namespace Database\Factories;

use App\Enums\TryoutSkill;
use App\Models\Tryout;
use App\Models\TryoutSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TryoutSection>
 */
class TryoutSectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tryout_id' => Tryout::factory(),
            'skill' => TryoutSkill::Listening,
            'ordering' => 1,
            'duration_seconds' => 1800,
            'transfer_seconds' => 0,
            'question_count' => 40,
            'is_adaptive' => true,
            'start_level' => 3,
            'questions_per_unit' => null,
            'audio_plays_practice' => 2,
            'audio_plays_exam' => 1,
        ];
    }
}
