<?php

namespace Database\Factories;

use App\Models\IqAnswer;
use App\Models\IqAttempt;
use App\Models\IqQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IqAnswer>
 */
class IqAnswerFactory extends Factory
{
    /**
     * Define the model's default state: a viewed question answered "A".
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'iq_attempt_id' => IqAttempt::factory(),
            'iq_question_id' => IqQuestion::factory(),
            'choice' => 'A',
            'is_correct' => true,
            'answered_at' => now(),
            'changes' => 0,
            'time_spent_seconds' => 0,
        ];
    }
}
