<?php

/**
 * The public GA4 tag (partials/analytics) only renders in production with a
 * measurement ID, and never on the Filament admin panel.
 */
function useAnalyticsEnvironment(string $environment, ?string $measurementId): void
{
    app()->detectEnvironment(fn (): string => $environment);
    config(['services.google_analytics.measurement_id' => $measurementId]);
}

test('production pages load the GA4 tag with manual page views', function () {
    useAnalyticsEnvironment('production', 'G-TEST');

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('googletagmanager.com/gtag/js?id=G-TEST', false)
        ->assertSee('send_page_view: false', false)
        ->assertSee("addEventListener('livewire:navigated', trackPageView)", false);
});

test('the GA4 tag is absent outside production', function () {
    useAnalyticsEnvironment('local', 'G-TEST');

    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('googletagmanager.com', false);
});

test('the GA4 tag is absent without a measurement id', function () {
    useAnalyticsEnvironment('production', null);

    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('googletagmanager.com', false);
});

test('the admin panel never loads the GA4 tag', function () {
    useAnalyticsEnvironment('production', 'G-TEST');

    $this->get('/admin/login')
        ->assertOk()
        ->assertDontSee('googletagmanager.com', false);
});
