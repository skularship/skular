<?php

namespace Database\Factories;

use App\Enums\TryoutAttemptStatus;
use App\Enums\TryoutMode;
use App\Models\Tryout;
use App\Models\TryoutAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TryoutAttempt>
 */
class TryoutAttemptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'tryout_id' => Tryout::factory(),
            'mode' => TryoutMode::Practice,
            'status' => TryoutAttemptStatus::InProgress,
            'start_level' => 3,
            'started_at' => now(),
        ];
    }
}
