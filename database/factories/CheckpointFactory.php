<?php

namespace Database\Factories;

use App\Enums\CheckpointStatus;
use App\Models\Checkpoint;
use App\Models\Fase;
use App\Models\Module;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Checkpoint>
 */
class CheckpointFactory extends Factory
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
            'fase_id' => Fase::factory(),
            'module_id' => fn (array $attributes): int => Module::factory()
                ->create(['fase_id' => $attributes['fase_id']])
                ->getKey(),
            'status' => CheckpointStatus::Available,
            'score' => null,
            'attempts' => 0,
            'metadata' => null,
            'available_at' => now(),
            'started_at' => null,
            'completed_at' => null,
        ];
    }

    /**
     * Indicate that the checkpoint is locked.
     */
    public function locked(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => CheckpointStatus::Locked,
            'available_at' => null,
        ]);
    }

    /**
     * Indicate that the checkpoint has been started.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => CheckpointStatus::InProgress,
            'started_at' => now(),
        ]);
    }

    /**
     * Indicate that the checkpoint has been completed.
     */
    public function completed(int $score = 85): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => CheckpointStatus::Completed,
            'score' => $score,
            'attempts' => 1,
            'started_at' => now()->subHour(),
            'completed_at' => now(),
        ]);
    }
}
