<?php

use App\Support\CookieConsent;

/**
 * The public GA4 tag (partials/analytics) only renders in production with a
 * measurement ID, never on the Filament admin panel, and gtag.js never loads
 * before the visitor grants analytics consent in the cookie banner.
 */
function useAnalyticsEnvironment(string $environment, ?string $measurementId): void
{
    app()->detectEnvironment(fn (): string => $environment);
    config(['services.google_analytics.measurement_id' => $measurementId]);
}

test('without a cookie choice gtag.js is not loaded, only the measurement id', function () {
    useAnalyticsEnvironment('production', 'G-TEST');

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('<meta name="skular-analytics-id" content="G-TEST">', false)
        ->assertDontSee('googletagmanager.com', false);
});

test('after analytics consent gtag.js is loaded', function () {
    useAnalyticsEnvironment('production', 'G-TEST');

    $this->withUnencryptedCookie(CookieConsent::cookieName(), CookieConsent::value(true))
        ->get(route('home'))
        ->assertOk()
        ->assertSee('googletagmanager.com/gtag/js?id=G-TEST', false)
        ->assertSee('data-navigate-once', false);
});

test('after rejecting analytics gtag.js is not loaded', function () {
    useAnalyticsEnvironment('production', 'G-TEST');

    $this->withUnencryptedCookie(CookieConsent::cookieName(), CookieConsent::value(false))
        ->get(route('home'))
        ->assertOk()
        ->assertDontSee('googletagmanager.com', false);
});

test('consent given for an older cookie policy version does not load gtag.js', function () {
    useAnalyticsEnvironment('production', 'G-TEST');

    $this->withUnencryptedCookie(CookieConsent::cookieName(), '0.9:granted')
        ->get(route('home'))
        ->assertOk()
        ->assertDontSee('googletagmanager.com', false);
});

test('the GA4 tag is absent outside production', function () {
    useAnalyticsEnvironment('local', 'G-TEST');

    $this->withUnencryptedCookie(CookieConsent::cookieName(), CookieConsent::value(true))
        ->get(route('home'))
        ->assertOk()
        ->assertDontSee('googletagmanager.com', false)
        ->assertDontSee('skular-analytics-id', false);
});

test('the GA4 tag is absent without a measurement id', function () {
    useAnalyticsEnvironment('production', null);

    $this->withUnencryptedCookie(CookieConsent::cookieName(), CookieConsent::value(true))
        ->get(route('home'))
        ->assertOk()
        ->assertDontSee('googletagmanager.com', false)
        ->assertDontSee('skular-analytics-id', false);
});

test('the admin panel never loads the GA4 tag', function () {
    useAnalyticsEnvironment('production', 'G-TEST');

    $this->withUnencryptedCookie(CookieConsent::cookieName(), CookieConsent::value(true))
        ->get('/admin/login')
        ->assertOk()
        ->assertDontSee('googletagmanager.com', false)
        ->assertDontSee('skular-analytics-id', false);
});

test('the consent script sends manual, cleaned page views and can withdraw consent', function () {
    $script = file_get_contents(resource_path('js/cookie-consent.js'));

    expect($script)
        ->toContain('send_page_view: false')
        ->toContain("addEventListener('livewire:navigated'")
        ->toContain("replace(/^\\/reset-password\\/.*$/, '/reset-password')")
        ->toContain('ga-disable-')
        ->toContain('deleteAnalyticsCookies');
});
