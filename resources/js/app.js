/**
 * Scroll reveal for the marketing landing page.
 *
 * The hidden state lives behind the `js` class on <html>, which the head
 * partial only adds when IntersectionObserver is available and the visitor has
 * not asked for reduced motion. That keeps the page readable without
 * JavaScript, and the refresh below always reveals what is still pending so a
 * failed observer can never hide content for good.
 */
const REVEAL_SELECTOR = '[data-reveal]';
const REVEAL_CLASS = 'is-revealed';

let revealObserver = null;

const prefersReducedMotion = () =>
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

const revealAll = () => {
    document.querySelectorAll(REVEAL_SELECTOR).forEach((element) => {
        element.classList.add(REVEAL_CLASS);
    });
};

const initScrollReveal = () => {
    const elements = Array.from(document.querySelectorAll(REVEAL_SELECTOR));

    if (elements.length === 0) {
        return;
    }

    if (prefersReducedMotion() || !('IntersectionObserver' in window)) {
        revealAll();

        return;
    }

    if (revealObserver === null) {
        revealObserver = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (!entry.isIntersecting) {
                        return;
                    }

                    entry.target.classList.add(REVEAL_CLASS);
                    revealObserver.unobserve(entry.target);
                });
            },
            { rootMargin: '0px 0px -8% 0px', threshold: 0.12 },
        );
    }

    elements.forEach((element) => {
        if (element.classList.contains(REVEAL_CLASS)) {
            return;
        }

        // Anything already on screen should not wait for a scroll event.
        if (element.getBoundingClientRect().top < window.innerHeight * 0.9) {
            element.classList.add(REVEAL_CLASS);

            return;
        }

        revealObserver.observe(element);
    });

    // Safety net: never leave a section invisible if an observe call is missed.
    window.setTimeout(revealAll, 3000);
};

document.addEventListener('DOMContentLoaded', initScrollReveal);
document.addEventListener('livewire:navigated', initScrollReveal);

/**
 * Page-navigation progress bar.
 *
 * Livewire Navigate swaps the page without a browser reload, so the browser's
 * own loading indicator never appears. A thin brand-coloured bar keeps every
 * navigation visible without covering the page or causing layout shift.
 *
 * The bar is purely decorative: `aria-hidden` keeps it out of the accessibility
 * tree, and every navigation keeps its normal focus behaviour.
 */
const initNavigationProgress = () => {
    if (document.querySelector('.sk-page-progress') !== null) {
        return;
    }

    const bar = document.createElement('div');
    bar.className = 'sk-page-progress';
    bar.setAttribute('aria-hidden', 'true');
    document.body.appendChild(bar);

    let active = false;
    let progress = 0;
    let ticker = null;
    let safety = null;

    const paint = () => {
        bar.style.transform = `scaleX(${progress})`;
    };

    const finish = () => {
        if (!active) {
            return;
        }

        active = false;
        window.clearInterval(ticker);
        window.clearTimeout(safety);
        ticker = null;
        safety = null;

        progress = 1;
        paint();
        bar.classList.add('is-done');

        window.setTimeout(() => {
            bar.classList.remove('is-active', 'is-done');
            progress = 0;
            paint();
        }, 260);
    };

    const start = () => {
        if (active) {
            return;
        }

        active = true;
        progress = 0.08;
        bar.classList.remove('is-done');
        bar.classList.add('is-active');
        paint();

        ticker = window.setInterval(() => {
            progress = Math.min(progress + (0.92 - progress) * 0.12, 0.92);
            paint();
        }, 180);

        // Never leave the bar stuck if an event is missed.
        safety = window.setTimeout(finish, 12000);
    };

    document.addEventListener('livewire:navigating', start);
    document.addEventListener('livewire:navigated', finish);
    window.addEventListener('pageshow', finish);

    // Full-page links (auth screens, external forms) get the same feedback.
    document.addEventListener('click', (event) => {
        if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }

        const link = event.target.closest('a');

        if (link === null || link.hasAttribute('wire:navigate') || link.hasAttribute('download')) {
            return;
        }

        if ((link.getAttribute('target') ?? '_self') !== '_self') {
            return;
        }

        const href = link.getAttribute('href') ?? '';

        if (href === '' || href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('tel:')) {
            return;
        }

        if (new URL(link.href, window.location.href).origin !== window.location.origin) {
            return;
        }

        start();
    });
};

document.addEventListener('DOMContentLoaded', initNavigationProgress);
document.addEventListener('livewire:navigated', initNavigationProgress);

/**
 * Module player section transitions.
 *
 * Switching learning sections is an in-place Livewire update (`#[Url]` only
 * pushes the query string), so the browser keeps the scroll offset from the
 * previous section. The player dispatches `module-section-changed` after
 * Livewire has morphed the new section into the DOM; the app layout scrolls on
 * `window`, so restoring the top here works for desktop and mobile alike.
 */
const scrollLearningPlayerToTop = () => {
    window.requestAnimationFrame(() => {
        window.scrollTo({
            top: 0,
            left: 0,
            behavior: prefersReducedMotion() ? 'auto' : 'smooth',
        });
    });
};

/**
 * Keep the current module section visible in the horizontally scrolling step
 * list. On phones the five tabs are wider than the viewport, so moving to the
 * last section would otherwise hide the active tab off the right edge.
 */
const centerActiveModuleStep = () => {
    window.requestAnimationFrame(() => {
        document.querySelectorAll('[data-module-steps]').forEach((list) => {
            const active = list.querySelector('[aria-current="step"]');

            if (active === null || list.scrollWidth <= list.clientWidth) {
                return;
            }

            const listRect = list.getBoundingClientRect();
            const activeRect = active.getBoundingClientRect();
            const offset = (activeRect.left - listRect.left) - ((listRect.width - activeRect.width) / 2);

            list.scrollBy({
                left: offset,
                behavior: prefersReducedMotion() ? 'auto' : 'smooth',
            });
        });
    });
};

document.addEventListener('module-section-changed', () => {
    scrollLearningPlayerToTop();
    centerActiveModuleStep();
});

document.addEventListener('DOMContentLoaded', centerActiveModuleStep);
document.addEventListener('livewire:navigated', centerActiveModuleStep);

/**
 * Surface failed Livewire requests on the learning player.
 *
 * `wire:loading` already clears itself when a request fails, so the missing
 * piece is telling the mentee what happened and that the same action can be
 * retried. The copy comes from the player element so it stays translatable.
 */
const showLearningRequestError = () => {
    const player = document.querySelector('[data-learning-player]');

    if (player === null) {
        return;
    }

    window.dispatchEvent(new CustomEvent('toast', {
        detail: {
            variant: 'warning',
            text: player.dataset.requestErrorMessage ?? '',
        },
    }));
};

const initLearningRequestFeedback = () => {
    window.Livewire.hook('request', ({ fail }) => {
        fail(() => showLearningRequestError());
    });
};

if (typeof window.Livewire === 'undefined') {
    document.addEventListener('livewire:init', initLearningRequestFeedback, { once: true });
} else {
    initLearningRequestFeedback();
}
