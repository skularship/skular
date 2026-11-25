<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Enums\IqSubtest;
use App\Models\IqQuestion;
use App\Models\IqTest;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<IqTest>
 */
class IqTestFactory extends Factory
{
    /**
     * Default question layout of the spec: subtest => count (35 questions in total).
     *
     * @var array<string, int>
     */
    public const LAYOUT = ['numerik' => 7, 'visuospasial' => 7, 'verbal' => 5, 'logika' => 8, 'teks' => 8];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'IQ-'.Str::upper(Str::random(6)),
            'title' => 'Tes IQ Latihan',
            'intro' => null,
            'duration_minutes' => 20,
            'status' => ContentStatus::Published,
        ];
    }

    /**
     * A draft test.
     */
    public function draft(): static
    {
        return $this->state(['status' => ContentStatus::Draft]);
    }

    /**
     * Add active questions numbered from 1 in subtest order, every key "A".
     *
     * @param  array<string, int>  $layout  Subtest value => question count.
     */
    public function withQuestions(array $layout = self::LAYOUT): static
    {
        return $this->afterCreating(function (IqTest $test) use ($layout): void {
            $number = 1;

            foreach ($layout as $subtest => $count) {
                for ($i = 0; $i < $count; $i++) {
                    IqQuestion::factory()->for($test, 'test')->create([
                        'number' => $number++,
                        'subtest' => IqSubtest::from($subtest),
                    ]);
                }
            }
        });
    }
}
