{{--
    Custom 404 — served by Laravel's error handling (resources/views/errors/404.blade.php),
    so the response keeps its 404 status code and JSON requests still get JSON.

    This document is deliberately standalone: it loads only the brand stylesheet
    and the local Rubik webfont, runs no queries, and keeps every custom rule
    scoped under #sk-404 with sk404-* keyframes so nothing can leak into other
    pages.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" style="font-family: 'Rubik', sans-serif; -webkit-tap-highlight-color: transparent;">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="color-scheme" content="light" />
    <meta name="robots" content="noindex, follow" />

    <title>404 — Halaman Tidak Ditemukan | Skular</title>
    <meta name="description" content="Halaman yang kamu tuju tidak ditemukan. Kembali ke beranda dan lanjutkan perjalananmu bersama Skular." />

    <link rel="icon" href="/favicon.png" type="image/png" />

    @fonts
    @vite(['resources/css/app.css'])

    <style>
        #sk-404 {
            position: relative;
            isolation: isolate;
            min-height: 100svh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            background:
                radial-gradient(120% 85% at 8% -10%, rgba(242, 201, 76, .30), rgba(242, 201, 76, 0) 55%),
                radial-gradient(90% 70% at 105% 108%, rgba(86, 165, 178, .26), rgba(86, 165, 178, 0) 60%),
                #fdfaef;
        }

        /* ---------- decorative layer ---------- */

        #sk-404 .sk404-blob {
            position: absolute;
            z-index: -2;
            border-radius: 9999px;
            filter: blur(46px);
            pointer-events: none;
        }

        #sk-404 .sk404-blob--teal {
            width: min(38rem, 78vw);
            height: min(38rem, 78vw);
            right: -14%;
            top: -18%;
            background: radial-gradient(circle at 35% 35%, rgba(86, 165, 178, .55), rgba(86, 165, 178, 0) 68%);
            animation: sk404-drift 26s ease-in-out infinite;
        }

        #sk-404 .sk404-blob--amber {
            width: min(30rem, 72vw);
            height: min(30rem, 72vw);
            left: -12%;
            bottom: -16%;
            background: radial-gradient(circle at 60% 40%, rgba(242, 153, 74, .38), rgba(242, 201, 76, 0) 66%);
            animation: sk404-drift 30s ease-in-out infinite reverse;
        }

        #sk-404 .sk404-stage {
            position: relative;
            isolation: isolate;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        #sk-404 .sk404-trail {
            position: absolute;
            z-index: -1;
            left: -6%;
            bottom: 8%;
            width: 112%;
            height: auto;
            opacity: .7;
            pointer-events: none;
        }

        #sk-404 .sk404-dot {
            position: absolute;
            border-radius: 9999px;
            pointer-events: none;
            animation: sk404-float 7s ease-in-out infinite;
        }

        #sk-404 .sk404-dot--a {
            width: 14px;
            height: 14px;
            left: 4%;
            top: 4%;
            background: #f2994a;
            animation-delay: -2s;
        }

        #sk-404 .sk404-dot--b {
            width: 10px;
            height: 10px;
            right: 26%;
            top: 46%;
            background: #56a5b2;
            animation-delay: -4.5s;
        }

        #sk-404 .sk404-dot--c {
            width: 8px;
            height: 8px;
            left: 34%;
            bottom: 4%;
            background: #f2c94c;
            animation-delay: -1s;
        }

        /* ---------- the 404 numerals ---------- */

        #sk-404 .sk404-number {
            position: relative;
            isolation: isolate;
            margin: 0;
            font-size: clamp(4.75rem, 22vw, 12.5rem);
            font-weight: 800;
            line-height: .84;
            letter-spacing: -.055em;
            color: #1c5f73;
        }

        #sk-404 .sk404-number::before,
        #sk-404 .sk404-number::after {
            content: attr(data-text);
            position: absolute;
            left: 0;
            top: 0;
            z-index: -1;
            color: #f2c94c;
            transform: translate(.11em, .11em);
        }

        #sk-404 .sk404-number::after {
            color: #f2994a;
            transform: translate(.055em, .055em);
        }

        #sk-404 .sk404-digit--zero {
            position: relative;
            color: #277c93;
        }

        #sk-404 .sk404-ring {
            position: absolute;
            z-index: -1;
            right: 16%;
            top: 8%;
            width: clamp(4.5rem, 13vw, 9rem);
            height: clamp(4.5rem, 13vw, 9rem);
            border: 3px dashed rgba(39, 124, 147, .38);
            border-radius: 9999px;
            animation: sk404-spin 24s linear infinite;
            pointer-events: none;
        }

        #sk-404 .sk404-underline {
            display: block;
            width: min(18rem, 72%);
            height: .6rem;
            margin-top: clamp(.75rem, 2vw, 1.5rem);
            border-radius: 9999px;
            background: linear-gradient(90deg, #f2c94c, #f0b34b 45%, #f2994a);
            transform: rotate(-1.4deg);
        }

        #sk-404 .sk404-illustration {
            width: min(13rem, 52vw);
            transform: rotate(-4deg);
            animation: sk404-float 6.5s ease-in-out infinite;
        }

        #sk-404 .sk404-illustration svg {
            display: block;
            width: 100%;
            height: auto;
            filter: drop-shadow(0 22px 34px rgba(28, 95, 115, .16));
        }

        /* ---------- copy ---------- */

        #sk-404 .sk404-grad {
            background-image: linear-gradient(135deg, #f0b34b, #f2994a 55%, #f2c94c);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        /* ---------- actions ---------- */

        #sk-404 .sk404-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            min-height: 3rem;
            padding: .8rem 1.6rem;
            border-radius: 9999px;
            font-size: .95rem;
            font-weight: 600;
            line-height: 1.2;
            text-align: center;
            transition: transform .18s ease, box-shadow .18s ease, background-color .18s ease, border-color .18s ease, filter .18s ease;
        }

        #sk-404 .sk404-btn:focus-visible {
            outline: none;
            box-shadow: 0 0 0 3px #fdfaef, 0 0 0 5px #277c93;
        }

        #sk-404 .sk404-btn:active {
            transform: translateY(1px);
        }

        #sk-404 .sk404-btn--primary {
            background-image: linear-gradient(135deg, #f2c94c, #f2994a);
            color: #1b1b18;
            box-shadow: 0 12px 26px -14px rgba(242, 153, 74, .85);
        }

        #sk-404 .sk404-btn--primary:hover {
            filter: brightness(1.05);
            transform: translateY(-2px);
            box-shadow: 0 18px 32px -16px rgba(242, 153, 74, .95);
        }

        #sk-404 .sk404-btn--ghost {
            background-color: rgba(255, 255, 255, .72);
            border: 2px solid rgba(39, 124, 147, .28);
            color: #1c5f73;
            backdrop-filter: blur(6px);
        }

        #sk-404 .sk404-btn--ghost:hover {
            border-color: #277c93;
            background-color: #fff;
            transform: translateY(-2px);
        }

        /* ---------- entrance ---------- */

        #sk-404 .sk404-rise {
            animation: sk404-rise .7s cubic-bezier(.16, 1, .3, 1) backwards;
            animation-delay: var(--d, 0ms);
        }

        @keyframes sk404-float {

            0%,
            100% {
                transform: rotate(-4deg) translateY(0);
            }

            50% {
                transform: rotate(-4deg) translateY(-14px);
            }
        }

        @keyframes sk404-drift {

            0%,
            100% {
                transform: translate3d(0, 0, 0) scale(1);
            }

            50% {
                transform: translate3d(-2.5rem, 2rem, 0) scale(1.06);
            }
        }

        @keyframes sk404-spin {
            to {
                transform: rotate(360deg);
            }
        }

        @keyframes sk404-rise {
            from {
                opacity: 0;
                transform: translateY(18px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (prefers-reduced-motion: reduce) {

            #sk-404 *,
            #sk-404 *::before,
            #sk-404 *::after {
                animation: none !important;
                transition-duration: .01ms !important;
            }
        }
    </style>
</head>

<body class="bg-warm-cream text-zinc-800 antialiased">
    <main id="sk-404">
        <span class="sk404-blob sk404-blob--teal" aria-hidden="true"></span>
        <span class="sk404-blob sk404-blob--amber" aria-hidden="true"></span>

        <div class="relative mx-auto flex w-full max-w-6xl grow flex-col justify-between gap-10 px-5 py-7 sm:px-8 sm:py-9 lg:px-10 lg:py-10">

            {{-- 1. Brand logo --}}
            <header class="sk404-rise" style="--d: 40ms">
                <a href="{{ route('home') }}" class="inline-flex items-center rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-teal focus-visible:ring-offset-4 focus-visible:ring-offset-warm-cream">
                    <img
                        src="/skular-logo.png"
                        alt="Skular"
                        width="530"
                        height="222"
                        class="block h-12 w-auto object-contain sm:h-14"
                    />
                </a>
            </header>

            <div class="grid items-center gap-9 lg:grid-cols-2 lg:gap-14">
                {{-- 2. Illustration + 3. oversized 404 --}}
                <div class="sk404-rise mx-auto w-full max-w-md lg:order-2 lg:max-w-none" style="--d: 160ms">
                    <div class="sk404-stage">
                        <span class="sk404-dot sk404-dot--a" aria-hidden="true"></span>
                        <span class="sk404-dot sk404-dot--b" aria-hidden="true"></span>
                        <span class="sk404-dot sk404-dot--c" aria-hidden="true"></span>

                        <svg class="sk404-trail" viewBox="0 0 360 120" fill="none" aria-hidden="true">
                            <path
                                d="M4 96C58 96 74 34 132 34s74 58 132 58 78-52 92-64"
                                stroke="#56A5B2"
                                stroke-width="3"
                                stroke-linecap="round"
                                stroke-dasharray="2 14"
                            />
                            <path d="M344 34l16 0 0 16" stroke="#56A5B2" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M350 28l10 6-10 6" stroke="#56A5B2" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>

                        <div class="sk404-illustration" role="img" aria-label="Sebuah halaman yang terlepas sedang dicari dengan kaca pembesar">
                            <svg viewBox="0 0 214 240" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <ellipse cx="102" cy="228" rx="66" ry="9" fill="#1C5F73" opacity=".14" />

                                <path
                                    d="M40 26h76l40 40v130a18 18 0 0 1-18 18H40a18 18 0 0 1-18-18V44a18 18 0 0 1 18-18z"
                                    fill="#FFFFFF"
                                    stroke="#277C93"
                                    stroke-width="5"
                                    stroke-linejoin="round"
                                />
                                <path d="M116 26l40 40h-40z" fill="#F2C94C" stroke="#277C93" stroke-width="5" stroke-linejoin="round" />

                                <path d="M50 100h74M50 126h86M50 152h52" stroke="#56A5B2" stroke-width="7" stroke-linecap="round" />
                                <path
                                    d="M50 180c14-11 24 9 38-2s24 7 38-4"
                                    stroke="#F2994A"
                                    stroke-width="6"
                                    stroke-linecap="round"
                                    stroke-dasharray="1 15"
                                />

                                <path d="M183 200l20 20" stroke="#F2994A" stroke-width="13" stroke-linecap="round" />
                                <circle cx="157" cy="176" r="34" fill="#FFFDF6" stroke="#F2994A" stroke-width="7" />
                                <text
                                    x="157"
                                    y="191"
                                    text-anchor="middle"
                                    font-family="Rubik, sans-serif"
                                    font-size="40"
                                    font-weight="700"
                                    fill="#277C93"
                                >?</text>

                                <path d="M30 74v16M22 82h16" stroke="#F2994A" stroke-width="5" stroke-linecap="round" />
                                <circle cx="196" cy="92" r="6" fill="#56A5B2" />
                                <circle cx="14" cy="140" r="5" fill="#F2C94C" />
                            </svg>
                        </div>

                        <p class="sk404-number" data-text="404" aria-hidden="true">
                            <span>4</span><span class="sk404-digit--zero">0</span><span>4</span>
                            <span class="sk404-ring" aria-hidden="true"></span>
                            <span class="sk404-underline" aria-hidden="true"></span>
                        </p>
                    </div>
                </div>

                {{-- 4. Headline, 5. description, 6. navigation --}}
                <section class="lg:order-1">
                    <h1
                        class="sk404-rise text-[2rem] leading-[1.08] font-extrabold tracking-tight text-zinc-900 sm:text-4xl lg:text-[3.1rem]"
                        style="--d: 260ms"
                    >
                        {{ __('Oops! Sepertinya Kamu') }}
                        <span class="sk404-grad block">{{ __('Tersesat.') }}</span>
                    </h1>

                    <p class="sk404-rise mt-4 max-w-xl text-base leading-relaxed text-zinc-600 sm:text-lg" style="--d: 340ms">
                        {{ __('Halaman yang kamu cari sedang tidak ada di sini — bisa jadi link-nya sudah berubah atau alamatnya kurang tepat. Tenang, perjalananmu bersama Skular tetap bisa dilanjutkan.') }}
                    </p>

                    <div class="sk404-rise mt-7 flex flex-col gap-3 sm:flex-row sm:items-center" style="--d: 420ms">
                        <a href="{{ route('home') }}" class="sk404-btn sk404-btn--primary w-full sm:w-auto"                        data-test="not-found-home"
>
                            {{ __('Kembali ke Beranda') }}
                            <svg viewBox="0 0 20 20" fill="none" class="size-4" aria-hidden="true">
                                <path d="M4 10h11M11 5.5 15.5 10 11 14.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </a>

                        <button
                            type="button"
                            class="sk404-btn sk404-btn--ghost w-full sm:w-auto"
                            data-sk404-back
                            data-test="not-found-back"
                        >
                            <svg viewBox="0 0 20 20" fill="none" class="size-4" aria-hidden="true">
                                <path d="M16 10H5M9 5.5 4.5 10 9 14.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            {{ __('Kembali Sebelumnya') }}
                        </button>
                    </div>

                    <p class="sk404-rise mt-6 text-sm text-zinc-500" style="--d: 500ms">
                        {{ __('Atau lanjutkan dari') }}
                        <a href="{{ route('home') }}" class="font-semibold text-brand-teal underline decoration-brand-amber decoration-2 underline-offset-4 transition hover:text-brand-teal-deep" data-test="not-found-home-inline">
                            {{ __('beranda Skular') }}
                        </a>.
                    </p>
                </section>
            </div>

            <footer class="flex flex-wrap items-center justify-between gap-3 border-t border-brand-teal/10 pt-5 text-xs text-zinc-400 sm:text-sm" style="animation: none">
                <span>{{ __('Skular — platform persiapan beasiswa untuk masa depanmu.') }}</span>
                <span aria-hidden="true" class="font-semibold tracking-wide text-brand-teal/70">404 · {{ __('Halaman tidak ditemukan') }}</span>
            </footer>
        </div>
    </main>

    <script>
        (function () {
            var home = @json(route('home'));

            document.querySelectorAll('[data-sk404-back]').forEach(function (button) {
                button.addEventListener('click', function () {
                    var sameOrigin = document.referrer && document.referrer.indexOf(window.location.origin) === 0;

                    if (sameOrigin && window.history.length > 1) {
                        window.history.back();

                        return;
                    }

                    window.location.assign(home);
                });
            });
        })();
    </script>
</body>

</html>