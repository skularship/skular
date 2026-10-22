<?php

namespace Database\Factories;

use App\Models\Module;
use App\Models\ModuleFeedback;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ModuleFeedback>
 */
class ModuleFeedbackFactory extends Factory
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
            'ratings' => fake()->randomElements([
                'Materi mudah dipahami',
                'Materi relevan',
                'AI feedback membantu',
                'Durasi sesuai',
                'Praktik membantu',
            ], fake()->numberBetween(1, 3)),
            'suggestion' => fake()->optional()->sentence(),
        ];
    }
}
