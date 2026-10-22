<?php

namespace App\Contracts;

use App\Exceptions\GoogleAuthException;
use App\Models\User;

interface GoogleAuthServiceInterface
{
    /**
     * Determine whether Google OAuth credentials are configured.
     */
    public function isConfigured(): bool;

    /**
     * Resolve the mentee for a Google account, linking or creating as needed.
     *
     * @param  array<string, mixed>  $google_user
     *
     * @throws GoogleAuthException
     */
    public function resolveUser(array $google_user): User;
}
