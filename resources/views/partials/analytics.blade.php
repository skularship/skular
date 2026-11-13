{{--
    Google Analytics 4 (gtag.js) page tracking, included from partials/head.

    Rendered only in production with services.google_analytics.measurement_id set,
    and never on /admin. Page views are sent manually (send_page_view: false):
    once on the first load and again on every wire:navigate visit. Livewire also
    fires livewire:navigated on the first load, so a page view is skipped when
    its URL equals the last one sent. Both scripts carry data-navigate-once so
    gtag.js is loaded a single time per full page load.

    No personal data: the query string and hash are dropped (Fortify puts the
    email in the reset-password link), and the password reset token and the
    email verification user id/hash are cut from the path.
--}}
@php
    $gaMeasurementId = config('services.google_analytics.measurement_id');
@endphp

@if (app()->environment('production') && filled($gaMeasurementId) && ! request()->is('admin', 'admin/*'))
    <script async data-navigate-once src="https://www.googletagmanager.com/gtag/js?id={{ urlencode($gaMeasurementId) }}"></script>
    <script data-navigate-once>
        (function () {
            if (window.skularAnalyticsBooted) {
                return;
            }
            window.skularAnalyticsBooted = true;

            var measurementId = @js($gaMeasurementId);
            var lastTrackedUrl = null;

            window.dataLayer = window.dataLayer || [];
            window.gtag = function () { window.dataLayer.push(arguments); };

            function sanitizedUrl(href) {
                var url = new URL(href, window.location.origin);
                var path = url.pathname
                    .replace(/^\/reset-password\/.*$/, '/reset-password')
                    .replace(/^\/email\/verify\/.*$/, '/email/verify');

                return url.origin + path;
            }

            function sanitizedReferrer() {
                if (! document.referrer) {
                    return undefined;
                }

                var referrer = new URL(document.referrer);

                return referrer.origin === window.location.origin
                    ? sanitizedUrl(document.referrer)
                    : referrer.origin + '/';
            }

            function trackPageView() {
                if (/^\/admin(\/|$)/.test(window.location.pathname)) {
                    return;
                }

                var pageLocation = sanitizedUrl(window.location.href);

                if (pageLocation === lastTrackedUrl) {
                    return;
                }

                var page = { page_location: pageLocation, page_title: document.title };
                var pageReferrer = lastTrackedUrl || sanitizedReferrer();

                if (pageReferrer) {
                    page.page_referrer = pageReferrer;
                }

                lastTrackedUrl = pageLocation;

                // Set globally so automatic events (scroll, engagement) reuse the clean URL too.
                window.gtag('set', page);
                window.gtag('event', 'page_view', page);
            }

            window.gtag('js', new Date());
            window.gtag('config', measurementId, {
                send_page_view: false,
                page_location: sanitizedUrl(window.location.href),
            });

            trackPageView();
            document.addEventListener('livewire:navigated', trackPageView);
        })();
    </script>
@endif
