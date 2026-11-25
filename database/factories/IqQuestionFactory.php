<?php

namespace Database\Factories;

use App\Enums\IqSubtest;
use App\Models\IqQuestion;
use App\Models\IqTest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IqQuestion>
 */
class IqQuestionFactory extends Factory
{
    /**
     * Define the model's default state: an active Numerik question with key A.
     * Visuospasial questions get an image path and alt text so they are valid.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'iq_test_id' => IqTest::factory(),
            'number' => fake()->unique()->numberBetween(1, 60000),
            'subtest' => IqSubtest::Numerik,
            'passage' => null,
            'prompt' => '2, 4, 6, 8, ?',
            'image_path' => null,
            'image_alt' => null,
            'option_a' => '10',
            'option_b' => '11',
            'option_c' => '12',
            'option_d' => '14',
            'answer_key' => 'A',
            'explanation' => 'Polanya selalu bertambah 2.',
            'is_active' => true,
        ];
    }

    /**
     * Fill the image of visuospatial questions so they pass activation rules.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (IqQuestion $question): void {
            if ($question->subtest === IqSubtest::Visuospasial && blank($question->image_path)) {
                $question->image_path = 'iq-questions/contoh-'.$question->number.'.png';
                $question->image_alt = 'Pola bentuk tiga baris dengan satu kotak kosong.';
            }
        });
    }

    /**
     * An inactive question.
     */
    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
