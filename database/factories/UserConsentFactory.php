<?php

namespace Database\Factories;

use App\Enums\ConsentSource;
use App\Enums\LegalDocument;
use App\Models\User;
use App\Models\UserConsent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserConsent>
 */
class UserConsentFactory extends Factory
{
    /**
     * Define the model's default state: the current Terms accepted at sign-up.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'document' => LegalDocument::Terms,
            'version' => LegalDocument::Terms->version(),
            'source' => ConsentSource::Register,
            'age_confirmed' => true,
            'accepted_at' => now(),
        ];
    }
}
