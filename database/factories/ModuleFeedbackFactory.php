<?php

namespace Database\Factories;

use App\Enums\FeedbackDifficulty;
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
            'helpful_score' => 4,
            'difficulty' => FeedbackDifficulty::TidakAda,
            'open_question' => 'Apa satu hal yang paling berguna dari modul ini buat aplikasimu?',
            'open_answer' => 'Contoh esai dan feedback-nya bikin aku paham struktur jawaban.',
            'low_score_reason' => null,
        ];
    }

    /**
     * Feedback sent with the old checklist form, before the mandatory questions.
     */
    public function legacy(): static
    {
        return $this->state(fn (array $attributes) => [
            'helpful_score' => null,
            'difficulty' => null,
            'open_question' => null,
            'open_answer' => null,
        ]);
    }
}
