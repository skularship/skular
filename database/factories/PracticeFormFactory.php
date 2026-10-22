<?php

namespace Database\Factories;

use App\Models\Module;
use App\Models\PracticeForm;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PracticeForm>
 */
class PracticeFormFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'module_id' => Module::factory(),
            'title' => fake()->randomElement([
                'Latihan Menulis Esai Motivasi',
                'Pengalaman Organisasi',
                'Rencana Studi',
                'Prestasi dan Penghargaan',
            ]),
            'description' => fake()->sentence(),
            'fields' => [
                [
                    'name' => 'jawaban',
                    'label' => 'Jawabanmu',
                    'type' => 'textarea',
                    'required' => true,
                    'placeholder' => 'Tulis jawabanmu di sini...',
                    'help_text' => 'Gunakan bahasa yang jelas dan runtut.',
                    'options' => [],
                ],
            ],
            'ordering' => 1,
            'is_active' => true,
            'include_in_cv' => false,
            'cv_ordering' => 0,
        ];
    }

    /**
     * Indicate that the answers populate a CV section.
     */
    public function includeInCv(int $cv_ordering = 1): static
    {
        return $this->state(fn (array $attributes): array => [
            'include_in_cv' => true,
            'cv_ordering' => $cv_ordering,
        ]);
    }
}
