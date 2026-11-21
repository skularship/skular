<?php

namespace Database\Factories;

use App\Enums\EventAudience;
use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state: a published webinar for everyone, three days from now.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = 'Webinar '.fake()->unique()->company();
        $starts_at = now()->addDays(3)->setTime(12, 0);

        return [
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(4)),
            'title' => $title,
            'type' => EventType::Webinar,
            'description' => 'Kita bahas cara menulis esai beasiswa yang jujur dan kuat.',
            'image_path' => null,
            'starts_at' => $starts_at,
            'ends_at' => $starts_at->addHours(2),
            'location_url' => 'https://meet.example.com/skular-webinar',
            'capacity' => null,
            'audience' => EventAudience::Semua,
            'invite_tag' => null,
            'registration_questions' => null,
            'status' => EventStatus::Published,
            'recording_url' => null,
            'is_free_preview' => false,
        ];
    }

    /**
     * A Pro event that already ended, has a recording, and is the Gratis preview.
     */
    public function freePreviewRecording(): static
    {
        return $this->past()->state(fn (array $attributes) => [
            'audience' => EventAudience::Pro,
            'recording_url' => 'https://www.youtube.com/watch?v=pratinjau',
            'is_free_preview' => true,
        ]);
    }

    /**
     * An event that is still a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => ['status' => EventStatus::Draft]);
    }

    /**
     * An event that already ended.
     */
    public function past(): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => now()->subDays(2),
            'ends_at' => now()->subDays(2)->addHours(2),
        ]);
    }

    /**
     * An event starting in the given number of minutes.
     */
    public function startingIn(int $minutes): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => now()->addMinutes($minutes),
            'ends_at' => now()->addMinutes($minutes + 120),
        ]);
    }
}
