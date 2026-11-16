{{--
    Google Analytics 4, consent-gated. Included from partials/head.

    Only in production with services.google_analytics.measurement_id set, and
    never on /admin. Nothing from Google loads before the visitor grants
    analytics consent in the cookie banner:

    - the measurement ID <meta> is always rendered here (it is not personal
      data and lets resources/js/cookie-consent.js start GA4 the moment the
      visitor accepts, without a reload);
    - the gtag.js <script> is rendered only when the consent cookie already
      says "granted" (App\Support\CookieConsent), so it starts downloading early.

    Initialisation, manual page views (send_page_view: false, again on every
    wire:navigate visit) and URL cleaning (query string, hash, password reset
    token and email verification id/hash removed) live in cookie-consent.js.
    data-navigate-once keeps gtag.js to a single load per full page load.
--}}
@php
    $gaMeasurementId = config('services.google_analytics.measurement_id');
@endphp

@if (app()->environment('production') && filled($gaMeasurementId) && ! request()->is('admin', 'admin/*'))
    <meta name="skular-analytics-id" content="{{ $gaMeasurementId }}">

    @if (\App\Support\CookieConsent::analyticsGranted(request()))
        <script async data-navigate-once data-skular-gtag src="https://www.googletagmanager.com/gtag/js?id={{ urlencode($gaMeasurementId) }}"></script>
    @endif
@endif
