<?php

namespace App\Services;

use App\Contracts\GoogleAuthServiceInterface;
use App\Exceptions\GoogleAuthException;
use App\Models\User;
use Illuminate\Support\Str;

class GoogleAuthService implements GoogleAuthServiceInterface
{
    /**
     * Determine whether Google OAuth credentials are configured.
     */
    public function isConfigured(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'))
            && filled(config('services.google.redirect'));
    }

    /**
     * Resolve the mentee for a Google account, linking or creating as needed.
     *
     * @param  array<string, mixed>  $google_user
     *
     * @throws GoogleAuthException
     */
    public function resolveUser(array $google_user): User
    {
        $google_id = $this->stringValue($google_user['id'] ?? null);
        $email = $this->stringValue($google_user['email'] ?? null);

        if ($google_id === null || $email === null) {
            throw new GoogleAuthException('Akun Google tidak mengirimkan email yang dapat digunakan.');
        }

        if (! filter_var($google_user['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            throw new GoogleAuthException('Email akun Google belum diverifikasi. Verifikasi email Google terlebih dahulu.');
        }

        $email = Str::lower($email);
        $name = $this->stringValue($google_user['name'] ?? null) ?? Str::before($email, '@');
        $avatar = $this->stringValue($google_user['avatar'] ?? null);

        $existing = User::query()->where('google_id', $google_id)->first();

        if ($existing instanceof User) {
            return $existing;
        }

        $user = User::query()->where('email', $email)->first();

        if ($user instanceof User) {
            $user->forceFill([
                'google_id' => $google_id,
                'avatar_url' => $avatar ?? $user->avatar_url,
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();

            return $user;
        }

        return User::query()->create([
            'name' => $name,
            'email' => $email,
            'google_id' => $google_id,
            'avatar_url' => $avatar,
            'email_verified_at' => now(),
            'password' => Str::password(32),
        ]);
    }

    /**
     * Extract a non-empty string from a Google payload value.
     */
    private function stringValue(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }
}
