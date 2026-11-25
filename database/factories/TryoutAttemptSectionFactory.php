<?php

namespace Database\Factories;

use App\Enums\TryoutSectionStatus;
use App\Enums\TryoutSkill;
use App\Models\TryoutAttempt;
use App\Models\TryoutAttemptSection;
use App\Models\TryoutSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TryoutAttemptSection>
 */
class TryoutAttemptSectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tryout_attempt_id' => TryoutAttempt::factory(),
            'tryout_section_id' => TryoutSection::factory(),
            'skill' => TryoutSkill::Listening,
            'status' => TryoutSectionStatus::Pending,
        ];
    }
}
