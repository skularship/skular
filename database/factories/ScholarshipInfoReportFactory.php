<?php

namespace Database\Factories;

use App\Enums\ReportedField;
use App\Enums\ReportStatus;
use App\Models\Scholarship;
use App\Models\ScholarshipInfoReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScholarshipInfoReport>
 */
class ScholarshipInfoReportFactory extends Factory
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
            'scholarship_id' => Scholarship::factory(),
            'field' => ReportedField::ClosingDate,
            'suggested_value' => 'Deadline diperpanjang sampai '.fake()->date('d M Y'),
            'source_url' => fake()->url(),
            'note' => null,
            'status' => ReportStatus::New,
        ];
    }

    /**
     * Indicate that an admin already decided the report.
     */
    public function reviewed(ReportStatus $status = ReportStatus::Accepted): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => $status,
            'reviewed_at' => now(),
        ]);
    }
}
