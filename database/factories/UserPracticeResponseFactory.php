<?php

namespace Database\Factories;

use App\Enums\AiFeedbackStatus;
use App\Models\Module;
use App\Models\PracticeForm;
use App\Models\User;
use App\Models\UserPracticeResponse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserPracticeResponse>
 */
class UserPracticeResponseFactory extends Factory
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
            'answers' => ['jawaban' => fake()->paragraph()],
            'ai_feedback' => null,
            'ai_score' => null,
            'ai_status' => AiFeedbackStatus::Pending,
            'ai_error' => null,
            'attempts' => 1,
            'submitted_at' => now(),
        ];
    }

    /**
     * Indicate that structured AI feedback has been stored.
     */
    public function withFeedback(int $score = 85): static
    {
        return $this->state(fn (array $attributes): array => [
            'ai_status' => AiFeedbackStatus::Completed,
            'ai_score' => $score,
            'ai_feedback' => [
                'score' => $score,
                'strengths' => ['Struktur jawaban sudah runtut.'],
                'improvements' => ['Tambahkan contoh konkret.'],
                'feedback' => 'Jawabanmu sudah baik dan sesuai dengan tujuan modul.',
                'next_steps' => ['Perkuat bagian penutup.'],
            ],
        ]);
    }

    /**
     * Indicate that the AI feedback request failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'ai_status' => AiFeedbackStatus::Failed,
            'ai_error' => 'AI feedback request timed out.',
        ]);
    }
}
