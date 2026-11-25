<?php

namespace Database\Factories;

use App\Models\TryoutAnswer;
use App\Models\TryoutAttemptSection;
use App\Models\TryoutQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TryoutAnswer>
 */
class TryoutAnswerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tryout_attempt_section_id' => TryoutAttemptSection::factory(),
            'tryout_question_id' => TryoutQuestion::factory(),
            'sequence' => 1,
            'level_at_answer' => 3,
            'served_at' => now(),
        ];
    }
}
