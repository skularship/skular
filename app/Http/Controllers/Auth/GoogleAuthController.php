<?php

namespace App\Http\Controllers\Auth;

use App\Contracts\GoogleAuthServiceInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\AbstractUser;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Throwable;

class GoogleAuthController extends Controller
{
    public function __construct(
        private readonly GoogleAuthServiceInterface $google_auth_service,
    ) {}

    /**
     * Redirect the mentee to Google's consent screen.
     */
    public function redirect(): SymfonyRedirectResponse
    {
        if (! $this->google_auth_service->isConfigured()) {
            return $this->unavailable();
        }

        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle the Google callback and sign the mentee in.
     */
    public function callback(): RedirectResponse
    {
        if (! $this->google_auth_service->isConfigured()) {
            return $this->unavailable();
        }

        try {
            $google_user = Socialite::driver('google')->user();

            $user = $this->google_auth_service->resolveUser([
                'id' => $google_user->getId(),
                'name' => $google_user->getName(),
                'email' => $google_user->getEmail(),
                // Google only returns email_verified in the raw user payload.
                // The service refuses to link an existing local account to an
                // unverified Google address, which would allow account takeover.
                'email_verified' => $google_user instanceof AbstractUser
                    && (bool) ($google_user->getRaw()['email_verified'] ?? false),
                'avatar' => $google_user->getAvatar(),
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Gagal masuk dengan Google. Silakan coba lagi atau gunakan email dan kata sandi.']);
        }

        Auth::login($user, remember: true);
        session()->regenerate();

        return redirect()->to(
            $user->hasCompletedProfile()
                ? route('dashboard', absolute: false)
                : route('profiling', absolute: false),
        );
    }

    /**
     * Fail gracefully when Google credentials are missing.
     */
    private function unavailable(): RedirectResponse
    {
        return redirect()
            ->route('login')
            ->withErrors(['email' => 'Login dengan Google sedang tidak tersedia. Silakan gunakan email dan kata sandi.']);
    }
}
