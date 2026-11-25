<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Enums\TryoutExamType;
use App\Enums\TryoutSkill;
use App\Models\Tryout;
use App\Models\TryoutQuestion;
use App\Models\TryoutSection;
use App\Models\TryoutUnit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tryout>
 */
class TryoutFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = 'Try Out IELTS Academic '.fake()->unique()->numberBetween(1, 99999);

        return [
            'code' => 'TO'.Str::upper(Str::random(6)),
            'exam_type' => TryoutExamType::Ielts,
            'variant' => 'academic',
            'title' => $title,
            'slug' => Str::slug($title),
            'description' => 'Kerjakan empat subtes berurutan.',
            'status' => ContentStatus::Published,
            'is_trial' => false,
            'requires_pro' => false,
            'ordering' => 0,
        ];
    }

    /**
     * Indicate that the Try Out is still a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => ContentStatus::Draft]);
    }

    /**
     * Add the four IELTS subtests with a small published pool each.
     *
     * Listening and Reading get $units units with $per_level questions on
     * each level 1..5 per unit, and serve $question_count questions.
     * Writing gets two essay tasks, Speaking three recording parts.
     */
    public function withIeltsSections(int $question_count = 4, int $units = 2, int $per_level = 2): static
    {
        return $this->afterCreating(function (Tryout $tryout) use ($question_count, $units, $per_level): void {
            foreach ([TryoutSkill::Listening, TryoutSkill::Reading, TryoutSkill::Writing, TryoutSkill::Speaking] as $index => $skill) {
                $objective = $skill->isObjective();

                $section = TryoutSection::factory()->create([
                    'tryout_id' => $tryout->id,
                    'skill' => $skill,
                    'ordering' => $index + 1,
                    'question_count' => $objective ? $question_count : $skill->defaultQuestionCount(),
                    'is_adaptive' => $objective,
                    'questions_per_unit' => $objective ? (int) ceil($question_count / $units) : null,
                    'transfer_seconds' => 0,
                ]);

                if ($objective) {
                    for ($u = 1; $u <= $units; $u++) {
                        $unit = TryoutUnit::factory()->create(['skill' => $skill]);
                        $section->units()->attach($unit->id, ['ordering' => $u]);

                        for ($level = 1; $level <= 5; $level++) {
                            TryoutQuestion::factory()->count($per_level)->for($unit, 'unit')->create([
                                'skill' => $skill,
                                'level' => $level,
                            ]);
                        }
                    }

                    continue;
                }

                $unit = TryoutUnit::factory()->create(['skill' => $skill]);
                $section->units()->attach($unit->id, ['ordering' => 1]);

                $is_writing = $skill === TryoutSkill::Writing;

                foreach ($is_writing ? [1, 2] : [1, 2, 3] as $part) {
                    $factory = $is_writing ? TryoutQuestion::factory()->essay($part) : TryoutQuestion::factory()->speaking($part);
                    $factory->for($unit, 'unit')->create();
                }
            }
        });
    }
}
