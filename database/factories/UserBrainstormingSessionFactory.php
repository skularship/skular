<?php

namespace Database\Factories;

use App\Models\Module;
use App\Models\PracticeForm;
use App\Models\User;
use App\Models\UserBrainstormingSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserBrainstormingSession>
 */
class UserBrainstormingSessionFactory extends Factory
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
            'module_id' => Module::factory(),
            'practice_form_id' => fn (array $attributes): int => PracticeForm::factory()
                ->create(['module_id' => $attributes['module_id']])
                ->getKey(),
            'answers' => [],
            'outline' => null,
            'summary' => null,
        ];
    }
}
