<?php

namespace App\Support;

use App\Enums\LegalDocument;
use Illuminate\Http\Request;

/**
 * Reads the visitor's cookie choice on the server.
 *
 * The cookie is written by resources/js/cookie-consent.js as
 * "<cookie policy version>:<granted|denied>" and is excluded from cookie
 * encryption in bootstrap/app.php. A choice for an older policy version
 * counts as no choice.
 */
class CookieConsent
{
    /**
     * The consent cookie name from config/legal.php.
     */
    public static function cookieName(): string
    {
        return (string) config('legal.consent_cookie.name');
    }

    /**
     * The cookie value for a choice under the current policy version.
     */
    public static function value(bool $analytics): string
    {
        return LegalDocument::Cookies->version().':'.($analytics ? 'granted' : 'denied');
    }

    /**
     * Whether the request carries analytics consent for the current policy version.
     */
    public static function analyticsGranted(Request $request): bool
    {
        $value = $request->cookie(self::cookieName());

        return is_string($value) && $value === self::value(true);
    }

    /**
     * Settings the banner script needs, as JSON for a <meta> tag.
     */
    public static function clientSettings(): string
    {
        return (string) json_encode([
            'name' => self::cookieName(),
            'days' => (int) config('legal.consent_cookie.days'),
            'version' => LegalDocument::Cookies->version(),
        ], JSON_UNESCAPED_SLASHES);
    }
}
