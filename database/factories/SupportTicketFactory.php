<?php

namespace Database\Factories;

use App\Enums\SupportTicketCategory;
use App\Enums\SupportTicketStatus;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupportTicket>
 */
class SupportTicketFactory extends Factory
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
            'category' => SupportTicketCategory::Bug,
            'subject' => 'Tombol simpan tidak merespons',
            'description' => fake()->paragraph(),
            'page_url' => null,
            'screenshot_path' => null,
            'user_agent' => 'Mozilla/5.0 (Test)',
            'status' => SupportTicketStatus::New,
        ];
    }

    /**
     * Indicate that the team already resolved the ticket.
     */
    public function resolved(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SupportTicketStatus::Resolved,
            'resolved_at' => now(),
        ]);
    }
}
