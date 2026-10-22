<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    class="scroll-smooth motion-reduce:scroll-auto"
>
    <head>
        @include('partials.head', [
            'description' => __('Kumpulan pertanyaan dan jawaban seputar Skular, pendaftaran beasiswa, administrasi, esai, wawancara, dan layanan Skular.'),
        ])
    </head>
    <body class="min-h-screen bg-warm-cream text-zinc-800 antialiased dark:bg-zinc-950 dark:text-zinc-100">
        <a
            href="#faq-main"
            class="sr-only z-[100] rounded-xl bg-brand-ink px-4 py-2 text-sm font-semibold text-white focus:not-sr-only focus:fixed focus:start-4 focus:top-4"
        >
            {{ __('Lewati ke konten utama') }}
        </a>

        <header class="sticky top-0 z-50 border-b border-brand-teal/12 bg-warm-cream/90 backdrop-blur-xl dark:border-white/10 dark:bg-zinc-950/85">
            <div class="mx-auto flex h-16 w-full max-w-6xl items-center justify-between gap-4 px-4 sm:h-18 sm:px-6">
                <x-brand.app-logo href="{{ route('home') }}" class="me-0" aria-label="{{ __('Beranda Skular') }}" />

                <nav class="flex items-center gap-1 sm:gap-3" aria-label="{{ __('Navigasi utama') }}">
                    {{-- On phones the logo already links home; hiding this link keeps the
                         remaining actions from being pushed past the viewport edge. --}}
                    <a
                        href="{{ route('home') }}"
                        class="hidden rounded-lg px-3 py-2 text-sm font-semibold text-zinc-600 transition hover:text-brand-teal sm:inline dark:text-zinc-300 dark:hover:text-brand-amber"
                    >
                        {{ __('Beranda') }}
                    </a>

                    <a
                        href="{{ route('faq.index') }}"
                        aria-current="page"
                        class="rounded-lg bg-brand-amber/25 px-3 py-2 text-sm font-semibold text-brand-teal dark:bg-brand-amber/15 dark:text-brand-amber"
                    >
                        {{ __('FAQ') }}
                    </a>

                    @auth
                        <x-actions.link-button :href="route('dashboard')" wire:navigate size="sm" iconTrailing="arrow-right">
                            {{ __('Dashboard') }}
                        </x-actions.link-button>
                    @else
                        <a
                            href="{{ route('login') }}"
                            class="hidden rounded-lg px-3 py-2 text-sm font-semibold text-zinc-600 transition hover:text-brand-teal sm:inline dark:text-zinc-300 dark:hover:text-brand-amber"
                        >
                            {{ __('Masuk') }}
                        </a>

                        <x-actions.link-button :href="route('register')" size="sm" iconTrailing="arrow-right">
                            {{ __('Mulai') }}
                        </x-actions.link-button>
                    @endauth
                </nav>
            </div>
        </header>

        <main id="faq-main">
            {{ $slot }}
        </main>

        <footer class="border-t border-white/10 bg-brand-ink dark:bg-zinc-950">
            <div class="mx-auto flex w-full max-w-6xl flex-col gap-4 px-4 py-10 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div>
                    <x-brand.app-logo href="{{ route('home') }}" class="me-0" />

                    <p class="mt-3 max-w-md text-sm leading-relaxed text-zinc-400">
                        {{ __('Platform persiapan beasiswa: profiling, matching beasiswa, roadmap belajar, dan latihan berskor.') }}
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-sm">
                    <a href="{{ route('home') }}" class="text-zinc-300 transition hover:text-brand-amber">
                        {{ __('Beranda') }}
                    </a>
                    <a href="{{ route('faq.index') }}" class="text-zinc-300 transition hover:text-brand-amber">
                        {{ __('FAQ') }}
                    </a>

                    @guest
                        <a href="{{ route('register') }}" class="text-zinc-300 transition hover:text-brand-amber">
                            {{ __('Daftar akun') }}
                        </a>
                    @endguest
                </div>
            </div>

            <div class="border-t border-white/10">
                <div class="mx-auto w-full max-w-6xl px-4 py-5 text-xs text-zinc-500 sm:px-6">
                    <p>&copy; {{ now()->year }} {{ config('app.name', 'Skular') }}. {{ __('Seluruh hak cipta dilindungi.') }}</p>
                </div>
            </div>
        </footer>
    </body>
</html>
