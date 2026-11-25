/**
 * Read-only viewer of the Try Out theory PDF (Alpine "tryoutPdfViewer", Pro only).
 *
 * The PDF is never offered as a download: the server sends the bytes masked
 * with a per-request XOR key (TryoutTheoryPdfController), only to fetch()
 * calls carrying the viewer header, so opening the URL directly shows nothing
 * and the network response is not a usable .pdf file. Pages are drawn onto
 * <canvas> elements by pdf.js (no text layer, so no copy and paste) and every
 * page carries a watermark with the mentee's name and email, so a screenshot
 * that leaks can be traced. Saving, printing and the context menu are blocked
 * inside the viewer. None of this stops a determined person with screen
 * capture; it removes the easy paths and makes leaks traceable.
 *
 * pdf.js is imported lazily, so it is only downloaded on the theory page.
 */

/** Header the server requires before it returns the masked PDF. @type {string} */
const VIEWER_HEADER = 'X-Skular-Viewer';

/** Response header carrying the base64 XOR key. @type {string} */
const KEY_HEADER = 'X-Skular-Key';

/** Smallest and largest zoom factor. @type {[number, number]} */
const ZOOM_RANGE = [0.6, 2.4];

/**
 * Undo the XOR mask applied by the server.
 *
 * @param {Uint8Array} bytes Masked bytes.
 * @param {Uint8Array} key XOR key (repeated over the bytes).
 * @returns {Uint8Array} The original PDF bytes.
 */
const unmask = (bytes, key) => {
    const out = new Uint8Array(bytes.length);

    for (let i = 0; i < bytes.length; i++) {
        out[i] = bytes[i] ^ key[i % key.length];
    }

    return out;
};

/**
 * Draw the diagonal, repeated watermark on a rendered page.
 *
 * @param {HTMLCanvasElement} canvas Rendered page.
 * @param {string} text Watermark text (name, email, date).
 * @returns {void}
 */
const drawWatermark = (canvas, text) => {
    const context = canvas.getContext('2d');

    if (! context) {
        return;
    }

    const size = Math.max(12, Math.round(canvas.width / 38));

    context.save();
    context.globalAlpha = 0.13;
    context.fillStyle = '#1c5f73';
    context.font = `700 ${size}px Rubik, sans-serif`;
    context.translate(canvas.width / 2, canvas.height / 2);
    context.rotate(-Math.PI / 6);

    const step_x = context.measureText(text).width + size * 4;
    const step_y = size * 7;
    const reach = Math.hypot(canvas.width, canvas.height);

    for (let y = -reach; y < reach; y += step_y) {
        const offset = (Math.round(y / step_y) % 2) * (step_x / 2);

        for (let x = -reach; x < reach; x += step_x) {
            context.fillText(text, x + offset, y);
        }
    }

    context.restore();
};

/**
 * Alpine component of the theory PDF viewer.
 *
 * @param {{ url: string, watermark: string }} config Masked PDF endpoint and watermark text.
 * @returns {object} Alpine component.
 */
const tryoutPdfViewer = (config) => {
    // pdf.js objects use private class fields, which break inside Alpine's
    // reactive proxies, so they live in this closure instead of the state.
    let pdf = null;
    let observer = null;
    const rendered = new Map();

    return {
    status: 'loading',
    error: '',
    pages: 0,
    current: 1,
    zoom: 1,

    async init() {
        this.blockShortcuts = this.blockShortcuts.bind(this);
        window.addEventListener('keydown', this.blockShortcuts, true);

        try {
            const pdfjs = await import('pdfjs-dist');
            // Bundled by Vite as a plain .js worker, so no server needs a MIME type for .mjs.
            const { default: PdfWorker } = await import('pdfjs-dist/build/pdf.worker.min.mjs?worker');
            pdfjs.GlobalWorkerOptions.workerPort ??= new PdfWorker();

            const response = await fetch(config.url, {
                headers: { [VIEWER_HEADER]: '1' },
                credentials: 'same-origin',
                cache: 'no-store',
            });

            if (! response.ok) {
                throw new Error(String(response.status));
            }

            const key = Uint8Array.from(atob(response.headers.get(KEY_HEADER) || ''), (c) => c.charCodeAt(0));
            const data = unmask(new Uint8Array(await response.arrayBuffer()), key);

            pdf = await pdfjs.getDocument({ data, isEvalSupported: false, enableXfa: false }).promise;
            this.pages = pdf.numPages;
            this.status = 'ready';

            await this.$nextTick();
            this.observePages();
        } catch (exception) {
            this.status = 'error';
            this.error = 'PDF teori belum bisa dibuka. Muat ulang halaman ini atau coba lagi sebentar lagi ya.';
        }
    },

    destroy() {
        window.removeEventListener('keydown', this.blockShortcuts, true);
        observer?.disconnect();
        pdf?.destroy();
    },

    /**
     * Render pages lazily as they come into view, and track the current page.
     *
     * @returns {void}
     */
    observePages() {
        observer?.disconnect();
        observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                const number = Number(entry.target.dataset.page);

                if (entry.isIntersecting) {
                    this.renderPage(number);

                    if (entry.intersectionRatio > 0.3) {
                        this.current = number;
                    }
                }
            });
        }, { root: this.$refs.scroller, rootMargin: '400px 0px', threshold: [0, 0.3] });

        this.$refs.scroller.querySelectorAll('[data-page]').forEach((element) => observer.observe(element));
    },

    /**
     * Draw one page (once per zoom level) with its watermark.
     *
     * @param {number} number 1-based page number.
     * @returns {Promise<void>}
     */
    async renderPage(number) {
        if (! pdf || rendered.get(number) === this.zoom) {
            return;
        }

        rendered.set(number, this.zoom);

        let page;

        try {
            page = await pdf.getPage(number);
        } catch (exception) {
            rendered.delete(number);

            return;
        }

        const holder = this.$refs.scroller.querySelector(`[data-page="${number}"]`);
        const width = Math.min(holder.clientWidth || 800, 1100);
        const base = page.getViewport({ scale: 1 });
        const ratio = window.devicePixelRatio || 1;
        const viewport = page.getViewport({ scale: (width / base.width) * this.zoom * ratio });

        const canvas = document.createElement('canvas');
        canvas.width = Math.floor(viewport.width);
        canvas.height = Math.floor(viewport.height);
        canvas.style.width = `${Math.floor(viewport.width / ratio)}px`;
        canvas.style.height = `${Math.floor(viewport.height / ratio)}px`;
        canvas.className = 'mx-auto block bg-white shadow-soft';

        await page.render({ canvas, viewport }).promise;
        drawWatermark(canvas, config.watermark);

        holder.replaceChildren(canvas);
        holder.style.minHeight = '';
    },

    /**
     * Change the zoom and redraw the visible pages.
     *
     * @param {number} delta Zoom step (positive or negative).
     * @returns {void}
     */
    setZoom(delta) {
        const next = Math.round(Math.min(ZOOM_RANGE[1], Math.max(ZOOM_RANGE[0], this.zoom + delta)) * 10) / 10;

        if (next === this.zoom) {
            return;
        }

        this.zoom = next;
        this.observePages();
    },

    /**
     * Scroll to a page.
     *
     * @param {number} number 1-based page number.
     * @returns {void}
     */
    goTo(number) {
        const target = Math.min(this.pages, Math.max(1, number));
        this.$refs.scroller.querySelector(`[data-page="${target}"]`)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    },

    /**
     * Block save, print and copy shortcuts while the viewer is on the page.
     *
     * @param {KeyboardEvent} event Key event.
     * @returns {void}
     */
    blockShortcuts(event) {
        if ((event.ctrlKey || event.metaKey) && ['s', 'p', 'c'].includes(event.key.toLowerCase())) {
            event.preventDefault();
            event.stopPropagation();
        }
    },
    };
};

document.addEventListener('alpine:init', () => {
    window.Alpine.data('tryoutPdfViewer', tryoutPdfViewer);
});
