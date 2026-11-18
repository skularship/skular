<?php

namespace Database\Factories;

use App\Enums\EventRegistrationStatus;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventRegistration>
 */
class EventRegistrationFactory extends Factory
{
    /**
     * Define the model's default state: an active registration without answers.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'user_id' => User::factory(),
            'answers' => null,
            'status' => EventRegistrationStatus::Registered,
            'reminded_at' => null,
            'feedback_score' => null,
            'feedback_text' => null,
        ];
    }
}
