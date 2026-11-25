<?php

namespace Database\Factories;

use App\Enums\IqAttemptStatus;
use App\Models\IqAttempt;
use App\Models\IqTest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IqAttempt>
 */
class IqAttemptFactory extends Factory
{
    /**
     * Define the model's default state: an attempt in progress with no questions.
     * Use IqAttemptServiceInterface::start() when the question set matters.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'iq_test_id' => IqTest::factory(),
            'status' => IqAttemptStatus::InProgress,
            'question_ids' => [],
            'started_at' => now(),
            'deadline_at' => now()->addMinutes(20),
            'total_questions' => 0,
        ];
    }
}
