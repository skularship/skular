/**
 * Cookie consent banner (Alpine "cookieConsent") and the consent-gated GA4 loader.
 *
 * The visitor's choice lives in a first-party cookie (name, lifetime and
 * policy version come from <meta name="skular-consent"> rendered by
 * partials/cookie-consent). Its value is "<cookie policy version>:<granted|denied>";
 * a choice made for an older policy version counts as no choice, so the
 * banner asks again.
 *
 * GA4 (gtag.js) never loads before analytics consent:
 * - partials/analytics renders <meta name="skular-analytics-id"> in production
 *   (never on /admin), and the gtag.js <script> tag only when the consent
 *   cookie already says "granted";
 * - this module initialises gtag after consent, injecting gtag.js itself when
 *   the visitor accepts on the current page (no reload needed);
 * - withdrawing consent switches GA off (ga-disable flag), stops page views and
 *   deletes the _ga cookies.
 *
 * Page views are sent manually (send_page_view: false) on the first load and
 * on every wire:navigate visit, with the query string, hash, password reset
 * token and email verification id/hash removed from every URL.
 */

/** @type {string} */
const GTAG_SRC = 'https://www.googletagmanager.com/gtag/js?id=';

/**
 * Read a <meta name="..."> content value.
 *
 * @param {string} name
 * @returns {string|null}
 */
const metaContent = (name) => document.querySelector(`meta[name="${name}"]`)?.getAttribute('content') || null;

/**
 * Consent cookie settings from the page.
 *
 * @returns {{name: string, days: number, version: string}|null}
 */
const consentSettings = () => {
    const raw = metaContent('skular-consent');

    if (! raw) {
        return null;
    }

    try {
        return JSON.parse(raw);
    } catch {
        return null;
    }
};

/**
 * Read a cookie by name.
 *
 * @param {string} name
 * @returns {string|null}
 */
const readCookie = (name) => {
    const prefix = `${name}=`;
    const match = document.cookie.split('; ').find((part) => part.startsWith(prefix));

    return match ? decodeURIComponent(match.slice(prefix.length)) : null;
};

/**
 * The stored analytics choice for the current policy version.
 *
 * @returns {'granted'|'denied'|null}
 */
export const storedChoice = () => {
    const settings = consentSettings();

    if (! settings) {
        return null;
    }

    const value = readCookie(settings.name);

    if (! value) {
        return null;
    }

    const [version, state] = value.split(':');

    if (version !== settings.version || (state !== 'granted' && state !== 'denied')) {
        return null;
    }

    return state;
};

/**
 * Persist the analytics choice.
 *
 * @param {'granted'|'denied'} state
 * @returns {void}
 */
const storeChoice = (state) => {
    const settings = consentSettings();

    if (! settings) {
        return;
    }

    const secure = window.location.protocol === 'https:' ? '; Secure' : '';
    const maxAge = Math.round(settings.days * 24 * 60 * 60);

    document.cookie = `${settings.name}=${encodeURIComponent(`${settings.version}:${state}`)}; Max-Age=${maxAge}; Path=/; SameSite=Lax${secure}`;
};

/**
 * Delete every Google Analytics cookie (_ga, _ga_<id>, _gid, _gat*) on this host and its parent domains.
 *
 * @returns {void}
 */
const deleteAnalyticsCookies = () => {
    const names = document.cookie
        .split('; ')
        .map((part) => part.split('=')[0])
        .filter((name) => /^(_ga|_gid|_gat)/.test(name));

    const parts = window.location.hostname.split('.');
    const domains = [''];

    for (let index = 0; index < parts.length - 1; index++) {
        domains.push(`; Domain=.${parts.slice(index).join('.')}`);
    }

    names.forEach((name) => {
        domains.forEach((domain) => {
            document.cookie = `${name}=; Max-Age=0; Path=/${domain}`;
        });
    });
};

/**
 * Remove personal data from a URL: query, hash, reset token and verification ids.
 *
 * @param {string} href
 * @returns {string}
 */
const sanitizedUrl = (href) => {
    const url = new URL(href, window.location.origin);
    const path = url.pathname
        .replace(/^\/reset-password\/.*$/, '/reset-password')
        .replace(/^\/email\/verify\/.*$/, '/email/verify');

    return url.origin + path;
};

/**
 * The referrer, reduced to the origin for other sites.
 *
 * @returns {string|undefined}
 */
const sanitizedReferrer = () => {
    if (! document.referrer) {
        return undefined;
    }

    const referrer = new URL(document.referrer);

    return referrer.origin === window.location.origin ? sanitizedUrl(document.referrer) : `${referrer.origin}/`;
};

const analytics = {
    /** @type {boolean} */
    booted: false,

    /** @type {boolean} */
    enabled: false,

    /** @type {string|null} */
    lastTrackedUrl: null,

    /**
     * The measurement ID, present only where GA may run (production, not /admin).
     *
     * @returns {string|null}
     */
    measurementId() {
        if (/^\/admin(\/|$)/.test(window.location.pathname)) {
            return null;
        }

        return metaContent('skular-analytics-id');
    },

    /**
     * Start GA4 after consent. Safe to call more than once.
     *
     * @returns {void}
     */
    enable() {
        const measurementId = this.measurementId();

        if (! measurementId) {
            return;
        }

        window[`ga-disable-${measurementId}`] = false;
        this.enabled = true;

        if (! this.booted) {
            this.booted = true;

            if (! document.querySelector('script[data-skular-gtag]')) {
                const script = document.createElement('script');
                script.async = true;
                script.src = GTAG_SRC + encodeURIComponent(measurementId);
                script.setAttribute('data-skular-gtag', '');
                script.setAttribute('data-navigate-once', '');
                document.head.appendChild(script);
            }

            window.dataLayer = window.dataLayer || [];
            window.gtag = function () {
                window.dataLayer.push(arguments);
            };

            window.gtag('js', new Date());
            window.gtag('config', measurementId, {
                send_page_view: false,
                page_location: sanitizedUrl(window.location.href),
            });

            document.addEventListener('livewire:navigated', () => this.trackPageView());
        }

        this.trackPageView();
    },

    /**
     * Stop GA4 after consent is withdrawn and delete its cookies.
     *
     * @returns {void}
     */
    disable() {
        const measurementId = this.measurementId();

        this.enabled = false;

        if (measurementId) {
            window[`ga-disable-${measurementId}`] = true;
        }

        deleteAnalyticsCookies();
    },

    /**
     * Send one page view for the current URL (skipped when it was the last one sent).
     *
     * @returns {void}
     */
    trackPageView() {
        if (! this.enabled || typeof window.gtag !== 'function' || ! this.measurementId()) {
            return;
        }

        const pageLocation = sanitizedUrl(window.location.href);

        if (pageLocation === this.lastTrackedUrl) {
            return;
        }

        const page = { page_location: pageLocation, page_title: document.title };
        const pageReferrer = this.lastTrackedUrl || sanitizedReferrer();

        if (pageReferrer) {
            page.page_referrer = pageReferrer;
        }

        this.lastTrackedUrl = pageLocation;

        // Set globally so automatic events (scroll, engagement) reuse the clean URL too.
        window.gtag('set', page);
        window.gtag('event', 'page_view', page);
    },
};

/**
 * Apply the stored choice: load GA4 only when analytics is granted.
 *
 * @returns {void}
 */
const applyStoredChoice = () => {
    if (storedChoice() === 'granted') {
        analytics.enable();
    }
};

/**
 * Alpine component for partials/cookie-consent.
 *
 * @returns {object}
 */
function cookieConsent() {
    return {
        open: false,
        settingsOpen: false,
        analyticsChecked: false,
        hasChoice: false,

        init() {
            const choice = storedChoice();

            this.hasChoice = choice !== null;
            this.analyticsChecked = choice === 'granted';
            this.open = ! this.hasChoice;
        },

        /**
         * Reopen the banner from a "Pengaturan cookie" link.
         *
         * @returns {void}
         */
        reopen() {
            const choice = storedChoice();

            this.analyticsChecked = choice === 'granted';
            this.open = true;
            this.settingsOpen = true;
            this.$nextTick(() => this.$refs.title?.focus());
        },

        acceptAll() {
            this.save('granted');
        },

        rejectAll() {
            this.save('denied');
        },

        saveSettings() {
            this.save(this.analyticsChecked ? 'granted' : 'denied');
        },

        /**
         * Store the choice, close the banner and start or stop analytics.
         *
         * @param {'granted'|'denied'} state
         * @returns {void}
         */
        save(state) {
            storeChoice(state);

            this.hasChoice = true;
            this.analyticsChecked = state === 'granted';
            this.open = false;
            this.settingsOpen = false;

            if (state === 'granted') {
                analytics.enable();
            } else {
                analytics.disable();
            }
        },
    };
}

document.addEventListener('alpine:init', () => {
    window.Alpine.data('cookieConsent', cookieConsent);
});

// "Pengaturan cookie" links anywhere on the page reopen the banner.
document.addEventListener('click', (event) => {
    const trigger = event.target instanceof Element ? event.target.closest('[data-cookie-settings]') : null;

    if (trigger) {
        event.preventDefault();
        window.dispatchEvent(new CustomEvent('skular:cookie-settings'));
    }
});

applyStoredChoice();
