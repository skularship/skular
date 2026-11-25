<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Enums\TryoutExamType;
use App\Enums\TryoutQuestionType;
use App\Enums\TryoutSkill;
use App\Models\TryoutQuestion;
use App\Models\TryoutUnit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TryoutQuestion>
 */
class TryoutQuestionFactory extends Factory
{
    /**
     * Define the model's default state: a single choice question with key B.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'Q-'.Str::upper(Str::random(10)),
            'tryout_unit_id' => TryoutUnit::factory(),
            'exam_type' => TryoutExamType::Ielts,
            'skill' => TryoutSkill::Reading,
            'level' => 3,
            'question_type' => TryoutQuestionType::SingleChoice,
            'prompt' => fake()->sentence().'?',
            'options' => ['A' => 'Pilihan A', 'B' => 'Pilihan B', 'C' => 'Pilihan C', 'D' => 'Pilihan D'],
            'answer_key' => 'B',
            'explanation' => 'RAHASIA-PEMBAHASAN '.fake()->sentence(),
            'max_score' => 1,
            'status' => ContentStatus::Published,
        ];
    }

    /**
     * A short text question with accepted alternatives.
     *
     * @param  array<int, string>  $accepted
     */
    public function shortText(string $key, array $accepted = [], ?int $word_limit = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'question_type' => TryoutQuestionType::ShortText,
            'options' => null,
            'answer_key' => $key,
            'accepted_answers' => $accepted,
            'word_limit' => $word_limit,
        ]);
    }

    /**
     * A Writing task (essay).
     */
    public function essay(int $part = 1): static
    {
        return $this->state(fn (array $attributes): array => [
            'skill' => TryoutSkill::Writing,
            'part' => $part,
            'question_type' => TryoutQuestionType::Essay,
            'options' => null,
            'answer_key' => null,
            'min_words' => $part === 1 ? 150 : 250,
            'rubric_key' => 'writing_v1',
            'prompt' => $part === 1
                ? 'The table shows the average monthly temperature and rainfall. Summarise the information.'
                : 'Some people think universities should focus on job skills. Discuss both views.',
        ]);
    }

    /**
     * A Speaking part (recording).
     */
    public function speaking(int $part = 1): static
    {
        return $this->state(fn (array $attributes): array => [
            'skill' => TryoutSkill::Speaking,
            'part' => $part,
            'question_type' => TryoutQuestionType::AudioRecording,
            'options' => null,
            'answer_key' => null,
            'prompt' => $part === 2 ? 'Describe a time when you helped someone.' : 'Let\'s talk about your hometown.',
            'prompt_items' => $part === 2
                ? ['who you helped', 'what the problem was', 'how you helped', 'and explain how you felt afterwards']
                : ['Where do you live?', 'What do you like about it?'],
            'prep_seconds' => $part === 2 ? 60 : null,
            'speak_seconds' => $part === 2 ? 120 : 240,
            'rubric_key' => 'speaking_v1',
        ]);
    }
}
