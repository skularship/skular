{{--
    "Skular sedang diperbarui" page, returned with status 503 + Retry-After by
    App\Http\Middleware\ShowUnderConstruction while UNDER_CONSTRUCTION is on.

    Standalone like errors/404: no layout, no database queries (logo is a static
    file, WhatsApp and Instagram come from config), light-only, Rubik webfont,
    custom rules scoped under #sk-uc with sk-uc-* names. Livewire does not
    auto-inject its assets into non-200 responses, so @livewireScripts loads
    Alpine here for the cookie banner and the "Pengaturan cookie" link.
    GA4 (partials/analytics) is left out on purpose.
--}}
@php
    $whatsapp_number = (string) config('services.support.whatsapp');
    $whatsapp_url = $whatsapp_number !== ''
        ? 'https://wa.me/'.$whatsapp_number.'?text='.rawurlencode('Halo Skular, saya mau tanya soal pembaruan website.')
        : null;
    $instagram_url = collect(config('seo.same_as', []))
        ->first(fn ($url): bool => is_string($url) && str_contains($url, 'instagram.com'));
    $instagram_handle = is_string($instagram_url) ? '@'.trim((string) parse_url($instagram_url, PHP_URL_PATH), '/') : null;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" style="font-family: 'Rubik', sans-serif; -webkit-tap-highlight-color: transparent;">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="color-scheme" content="light" />
    <meta name="robots" content="noindex" />

    <title>{{ __('Skular sedang diperbarui') }}</title>
    <meta name="description" content="{{ __('Skular sedang menyiapkan pembaruan. Progres belajar dan jawabanmu tetap aman.') }}" />

    <link rel="icon" href="/favicon.ico" sizes="any" />
    <link rel="icon" href="/favicon-48x48.png" type="image/png" sizes="48x48" />
    <link rel="apple-touch-icon" href="/apple-touch-icon.png" />

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <style>
        #sk-uc {
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

        #sk-uc .sk-uc-card {
            border: 1px solid rgba(39, 124, 147, .14);
            border-radius: 1.75rem;
            background-color: rgba(255, 255, 255, .78);
            box-shadow: 0 30px 60px -40px rgba(28, 95, 115, .45);
            backdrop-filter: blur(8px);
        }

        #sk-uc .sk-uc-illustration svg {
            display: block;
            width: 100%;
            height: auto;
        }

        #sk-uc .sk-uc-gear {
            transform-box: fill-box;
            transform-origin: center;
            animation: sk-uc-spin 14s linear infinite;
        }

        #sk-uc .sk-uc-gear--small {
            animation-direction: reverse;
            animation-duration: 9s;
        }

        #sk-uc .sk-uc-grad {
            background-image: linear-gradient(135deg, #f0b34b, #f2994a 55%, #f2c94c);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        #sk-uc .sk-uc-btn {
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
            background-image: linear-gradient(135deg, #f2c94c, #f2994a);
            color: #1b1b18;
            box-shadow: 0 12px 26px -14px rgba(242, 153, 74, .85);
            transition: transform .18s ease, filter .18s ease;
        }

        #sk-uc .sk-uc-btn:hover {
            filter: brightness(1.05);
            transform: translateY(-2px);
        }

        #sk-uc .sk-uc-btn:focus-visible {
            outline: none;
            box-shadow: 0 0 0 3px #fdfaef, 0 0 0 5px #277c93;
        }

        #sk-uc .sk-uc-rise {
            animation: sk-uc-rise .7s cubic-bezier(.16, 1, .3, 1) backwards;
            animation-delay: var(--d, 0ms);
        }

        @keyframes sk-uc-spin {
            to {
                transform: rotate(360deg);
            }
        }

        @keyframes sk-uc-rise {
            from {
                opacity: 0;
                transform: translateY(16px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (prefers-reduced-motion: reduce) {

            #sk-uc *,
            #sk-uc *::before,
            #sk-uc *::after {
                animation: none !important;
                transition-duration: .01ms !important;
            }
        }
    </style>
</head>

<body class="bg-warm-cream text-zinc-800 antialiased">
    @include('partials.cookie-consent')

    <main id="sk-uc" data-test="under-construction">
        <div class="mx-auto flex w-full max-w-3xl grow flex-col gap-8 px-4 py-6 sm:px-8 sm:py-9">
            <header class="sk-uc-rise" style="--d: 40ms">
                <img src="/skular-logo.png" alt="Skular" width="530" height="222" class="block h-11 w-auto object-contain sm:h-13" />
            </header>

            <section class="sk-uc-card sk-uc-rise flex grow flex-col items-center justify-center px-5 py-9 text-center sm:px-10 sm:py-12" style="--d: 140ms">
                <div class="sk-uc-illustration w-36 sm:w-44" aria-hidden="true">
                    <svg viewBox="0 0 160 140" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <ellipse cx="80" cy="130" rx="54" ry="7" fill="#1C5F73" opacity=".12" />
                        <rect x="22" y="22" width="96" height="96" rx="16" fill="#FFFFFF" stroke="#277C93" stroke-width="5" />
                        <path d="M40 52h44M40 72h56M40 92h32" stroke="#56A5B2" stroke-width="7" stroke-linecap="round" />
                        <g class="sk-uc-gear">
                            <circle cx="116" cy="94" r="20" fill="#F2C94C" stroke="#F2994A" stroke-width="5" />
                            <circle cx="116" cy="94" r="7" fill="#FFFDF6" stroke="#F2994A" stroke-width="4" />
                            <path d="M116 66v8M116 114v8M88 94h8M136 94h8M96 74l6 6M130 108l6 6M96 114l6-6M130 80l6-6" stroke="#F2994A" stroke-width="5" stroke-linecap="round" />
                        </g>
                        <g class="sk-uc-gear sk-uc-gear--small">
                            <circle cx="138" cy="44" r="10" fill="#56A5B2" />
                            <circle cx="138" cy="44" r="4" fill="#FFFFFF" />
                        </g>
                    </svg>
                </div>

                <h1 class="mt-6 text-[1.85rem] leading-tight font-extrabold tracking-tight text-zinc-900 sm:text-4xl">
                    {{ __('Skular sedang') }}
                    <span class="sk-uc-grad">{{ __('diperbarui') }}</span>
                </h1>

                <p class="mt-4 max-w-xl text-base leading-relaxed text-zinc-600 sm:text-lg">
                    {{ __('Kami sedang menyiapkan pembaruan supaya Skular makin nyaman dipakai. Progres belajar dan jawabanmu tetap aman, tidak ada yang hilang. Coba buka lagi sebentar lagi, ya.') }}
                </p>

                <button type="button" onclick="window.location.reload()" class="sk-uc-btn mt-7 w-full sm:w-auto" data-test="under-construction-reload">
                    <svg viewBox="0 0 20 20" fill="none" class="size-4" aria-hidden="true">
                        <path d="M16 10a6 6 0 1 1-1.76-4.24M16 4v3.5h-3.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    {{ __('Muat ulang halaman') }}
                </button>

                @if ($whatsapp_url !== null || $instagram_url !== null)
                    <div class="mt-7 flex flex-col items-center gap-2 text-sm text-zinc-600 sm:flex-row sm:gap-4" data-test="under-construction-help">
                        <span>{{ __('Butuh bantuan sekarang?') }}</span>
                        @if ($whatsapp_url !== null)
                            <a href="{{ $whatsapp_url }}" target="_blank" rel="noopener" class="font-semibold text-emerald-700 underline decoration-2 underline-offset-4 hover:text-emerald-800">
                                {{ __('Chat kami di WhatsApp') }}
                            </a>
                        @endif
                        @if ($instagram_url !== null)
                            <a href="{{ $instagram_url }}" target="_blank" rel="noopener" class="font-semibold text-brand-teal underline decoration-brand-amber decoration-2 underline-offset-4 hover:text-brand-teal-deep">
                                {{ __('Instagram') }} {{ $instagram_handle }}
                            </a>
                        @endif
                    </div>
                @endif
            </section>

            <footer class="flex flex-col gap-3 border-t border-brand-teal/10 pt-5 text-xs text-zinc-500 sm:flex-row sm:items-center sm:justify-between sm:text-sm">
                <span>{{ __('Skular — platform persiapan beasiswa.') }}</span>
                <x-legal.links link-class="text-zinc-500 transition hover:text-brand-teal" />
            </footer>
        </div>
    </main>

    @livewireScripts
</body>

</html>
