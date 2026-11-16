<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Contracts\ConsentServiceInterface;
use App\Enums\ConsentSource;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

/**
 * Fortify action that registers a new mentee from the sign-up form.
 *
 * Only name, email and password are stored here; the scholarship profile is
 * filled afterwards in the profiling wizard (see RegisterResponse). The
 * Terms/Privacy checkbox and the age statement are required here on the
 * server and logged as consent (source register) together with the account.
 */
class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Inject the consent log.
     */
    public function __construct(private readonly ConsentServiceInterface $consents) {}

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException When a field or a consent statement is missing.
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            'terms' => ['accepted'],
            'age_confirmation' => ['accepted'],
        ], [
            'terms.accepted' => __('Centang persetujuan Syarat & Ketentuan dan Kebijakan Privasi untuk mendaftar.'),
            'age_confirmation.accepted' => __('Centang pernyataan usia untuk mendaftar.'),
        ])->validate();

        return DB::transaction(function () use ($input): User {
            $user = User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => $input['password'],
            ]);

            $this->consents->record($user, $this->consents->requiredDocuments(), ConsentSource::Register, true);

            return $user;
        });
    }
}
