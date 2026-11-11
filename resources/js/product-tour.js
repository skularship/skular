/**
 * Alpine component "productTour": onboarding coach marks for one page.
 *
 * Used by resources/views/components/onboarding/tour-overlay.blade.php.
 * Config: { key, steps: [{ target, title, body }], autoStart, persist }.
 * - Opens by itself when autoStart is true (server: tour not finished yet;
 *   local: no localStorage flag yet), when the URL has ?tour=<key>, or when a
 *   `product-tour:start` window event names this key (or no key).
 * - A step whose target is missing or hidden (e.g. the desktop sidebar on a
 *   phone) is shown as a centred card instead of failing.
 * - Finishing or skipping is remembered: $wire.complete() for signed-in
 *   mentees, localStorage for guests on the landing page.
 */
const reducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

const SPOTLIGHT_PADDING = 8;
const CARD_WIDTH = 340;
const CARD_GAP = 14;

export function productTour(config) {
    return {
        key: config.key,
        steps: Array.isArray(config.steps) ? config.steps : [],
        autoStart: Boolean(config.autoStart),
        persist: config.persist === 'local' ? 'local' : 'server',
        open: false,
        index: 0,
        rect: null,
        returnFocus: null,
        listeners: [],

        init() {
            if (this.steps.length === 0) {
                return;
            }

            const forced = new URLSearchParams(window.location.search).get('tour') === this.key;

            if (this.persist === 'local' && this.readLocalFlag()) {
                this.autoStart = false;
            }

            this.listen(window, 'resize', () => this.open && this.measure());
            this.listen(window, 'scroll', () => this.open && this.measure());
            this.listen(window, 'product-tour:start', (event) => {
                if (! event.detail || event.detail === this.key) {
                    this.start();
                }
            });

            if (forced || this.autoStart) {
                // Let the page (and its scroll reveal) settle before the first spotlight.
                window.setTimeout(() => this.start(), reducedMotion() ? 0 : 500);
            }
        },

        destroy() {
            this.listeners.forEach(([target, type, handler]) => target.removeEventListener(type, handler));
            this.listeners = [];
        },

        get step() {
            return this.steps[this.index] ?? null;
        },

        get isLast() {
            return this.index >= this.steps.length - 1;
        },

        start() {
            if (this.steps.length === 0) {
                return;
            }

            this.returnFocus = document.activeElement;
            this.index = 0;
            this.open = true;
            this.show();
        },

        next() {
            if (this.isLast) {
                this.close(true);

                return;
            }

            this.index++;
            this.show();
        },

        prev() {
            if (this.index === 0) {
                return;
            }

            this.index--;
            this.show();
        },

        skip() {
            this.close(true);
        },

        close(remember) {
            this.open = false;
            this.rect = null;

            if (remember) {
                this.remember();
            }

            if (this.returnFocus && typeof this.returnFocus.focus === 'function') {
                this.returnFocus.focus({ preventScroll: true });
            }
        },

        show() {
            const element = this.target();

            if (! element) {
                this.rect = null;
                this.$nextTick(() => this.$refs.card?.focus({ preventScroll: true }));

                return;
            }

            element.scrollIntoView({ block: 'center', behavior: reducedMotion() ? 'auto' : 'smooth' });

            window.setTimeout(() => {
                this.measure();
                this.$refs.card?.focus({ preventScroll: true });
            }, reducedMotion() ? 0 : 320);
        },

        target() {
            const selector = this.step?.target;

            if (! selector) {
                return null;
            }

            let candidates = [];

            try {
                candidates = Array.from(document.querySelectorAll(selector));
            } catch {
                return null;
            }

            // The first element that is actually rendered and visible.
            return candidates.find((element) => {
                const box = element.getBoundingClientRect();

                return box.width > 0 && box.height > 0 && window.getComputedStyle(element).visibility !== 'hidden';
            }) ?? null;
        },

        measure() {
            const element = this.target();

            if (! element) {
                this.rect = null;

                return;
            }

            const box = element.getBoundingClientRect();

            this.rect = {
                top: box.top - SPOTLIGHT_PADDING,
                left: box.left - SPOTLIGHT_PADDING,
                width: box.width + SPOTLIGHT_PADDING * 2,
                height: box.height + SPOTLIGHT_PADDING * 2,
            };
        },

        spotlightStyle() {
            if (! this.rect) {
                return '';
            }

            return `top:${this.rect.top}px;left:${this.rect.left}px;width:${this.rect.width}px;height:${this.rect.height}px;`;
        },

        cardStyle() {
            if (! this.rect) {
                return '';
            }

            const viewportWidth = window.innerWidth;
            const viewportHeight = window.innerHeight;

            // On phones the card docks to the bottom edge so it never covers the target awkwardly.
            if (viewportWidth < 640) {
                const nearBottom = this.rect.top + this.rect.height > viewportHeight * 0.6;

                return nearBottom ? 'left:12px;right:12px;top:12px;' : 'left:12px;right:12px;bottom:12px;';
            }

            const width = Math.min(CARD_WIDTH, viewportWidth - 32);
            const left = Math.min(Math.max(16, this.rect.left), viewportWidth - width - 16);
            const below = this.rect.top + this.rect.height + CARD_GAP;
            const fitsBelow = below + 220 < viewportHeight;
            const top = fitsBelow ? below : Math.max(16, this.rect.top - CARD_GAP - 220);

            return `left:${left}px;top:${top}px;width:${width}px;`;
        },

        remember() {
            if (this.persist === 'local') {
                try {
                    window.localStorage.setItem(`skular.tour.${this.key}`, '1');
                } catch {
                    // Private mode or blocked storage: the tour may simply show again.
                }

                return;
            }

            if (this.$wire && typeof this.$wire.complete === 'function') {
                this.$wire.complete();
            }
        },

        readLocalFlag() {
            try {
                return window.localStorage.getItem(`skular.tour.${this.key}`) === '1';
            } catch {
                return false;
            }
        },

        listen(target, type, handler) {
            target.addEventListener(type, handler, { passive: true });
            this.listeners.push([target, type, handler]);
        },
    };
}

// Livewire starts Alpine on DOMContentLoaded, after this module has run.
document.addEventListener('alpine:init', () => {
    window.Alpine.data('productTour', productTour);
});
