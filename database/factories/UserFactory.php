<?php

namespace Database\Factories;

use App\Enums\ConsentSource;
use App\Enums\EducationLevel;
use App\Enums\LegalDocument;
use App\Enums\LocationScope;
use App\Models\Cluster;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            // Like the consent rows in configure(): a factory mentee already
            // agreed to the module feedback commitment. Use
            // withoutFeedbackCommitment() to test the commitment screen.
            'feedback_commitment_at' => now(),
        ];
    }

    /**
     * Indicate that the mentee has not agreed to the feedback commitment yet.
     */
    public function withoutFeedbackCommitment(): static
    {
        return $this->state(fn (array $attributes) => [
            'feedback_commitment_at' => null,
        ]);
    }

    /**
     * Every factory mentee has accepted the current Terms and Privacy Policy,
     * like a mentee who signed up normally, so the EnsureLegalConsent
     * middleware lets them in. Use withoutConsent() for the re-consent flow.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (User $user): void {
            foreach ([LegalDocument::Terms, LegalDocument::Privacy] as $document) {
                $user->consents()->create([
                    'document' => $document,
                    'version' => $document->version(),
                    'source' => ConsentSource::Register,
                    'age_confirmed' => true,
                    'accepted_at' => now(),
                ]);
            }
        });
    }

    /**
     * Indicate that the mentee has not accepted any legal document yet
     * (an existing account from before the consent log, or a new Google account).
     */
    public function withoutConsent(): static
    {
        return $this->afterCreating(function (User $user): void {
            $user->consents()->delete();
        });
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the model has two-factor authentication configured.
     */
    public function withTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_secret' => encrypt('secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-1'])),
            'two_factor_confirmed_at' => now(),
        ]);
    }

    /**
     * Indicate that the mentee has completed the profiling wizard.
     */
    public function profiled(): static
    {
        return $this->state(fn (array $attributes) => [
            'level' => EducationLevel::S1,
            'age' => 21,
            'gpa' => 3.75,
            'has_sktm' => false,
            'desired_level' => EducationLevel::S2,
            'desired_location' => LocationScope::LuarNegeri,
            'cluster_id' => Cluster::factory(),
            'phone' => '081234567890',
            'profile_completed_at' => now(),
        ]);
    }
}
