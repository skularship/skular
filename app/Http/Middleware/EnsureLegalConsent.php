<?php

namespace App\Http\Middleware;

use App\Contracts\ConsentServiceInterface;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends a signed-in mentee to the consent screen (route legal.consent) until
 * they accept the current Terms and Privacy Policy.
 *
 * Covers three cases with one check: an account created by the first Google
 * sign-in, an account from before the consent log, and a mentee who accepted
 * an older document version. Applied to the auth+verified app routes only, so
 * the legal pages, logout, settings (account deletion) and the health check
 * stay reachable. A positive result is cached in the session per version key.
 */
class EnsureLegalConsent
{
    /**
     * Session key holding the version key the mentee was last checked against.
     */
    public const SESSION_KEY = 'legal.consent_version';

    /**
     * Inject the consent log.
     */
    public function __construct(private readonly ConsentServiceInterface $consents) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        $version_key = $this->consents->versionKey();

        if ($request->session()->get(self::SESSION_KEY) === $version_key) {
            return $next($request);
        }

        if ($this->consents->hasCurrentConsent($user)) {
            $request->session()->put(self::SESSION_KEY, $version_key);

            return $next($request);
        }

        if ($request->isMethod('GET') && ! $request->expectsJson()) {
            $request->session()->put('url.intended', $request->fullUrl());
        }

        return redirect()->route('legal.consent');
    }
}
