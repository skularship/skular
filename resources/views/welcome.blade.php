@php
    /**
     * Landing page copy.
     *
     * Everything here describes features that actually exist in the app
     * (8-step profiling, scholarship matching, roadmap modules, practice forms,
     * AI feedback, progress tracking). No numbers, ratings or testimonials are
     * invented.
     */
    $navLinks = [
        ['href' => '#manfaat', 'label' => 'Manfaat'],
        ['href' => '#cara-kerja', 'label' => 'Cara Kerja'],
        ['href' => '#cek-kesiapan', 'label' => 'Cek Kesiapan'],
        ['href' => '#persiapan', 'label' => 'Persiapan'],
    ];

    $heroRotatingWords = ['Lebih Siap.', 'Lebih Terarah.', 'Lebih Percaya Diri.'];

    $heroFacts = [
        ['value' => '8', 'label' => 'Langkah profiling'],
        ['value' => '3', 'label' => 'Fase roadmap belajar'],
        ['value' => '24/7', 'label' => 'Feedback latihan'],
    ];

    $valueMarquee = [
        'Temukan Beasiswamu',
        'Bangun Kemampuanmu',
        'Persiapan yang Terarah',
        'Masa Depan Dimulai Sekarang',
    ];

    $brandMarquee = ['Belajar', 'Bersiap', 'Bertumbuh', 'Berprestasi'];

    $journey = [
        [
            'number' => '01',
            'icon' => 'identification',
            'title' => 'Kenali tujuanmu',
            'text' => 'Isi profiling delapan langkah: jenjang, usia, IPK, dan preferensi studimu. Jawabanmu jadi dasar semua rekomendasi.',
        ],
        [
            'number' => '02',
            'icon' => 'magnifying-glass',
            'title' => 'Temukan peluang',
            'text' => 'Skular mencocokkan profilmu dengan beasiswa aktif dan menjelaskan alasan setiap kecocokan secara terbuka.',
        ],
        [
            'number' => '03',
            'icon' => 'book-open',
            'title' => 'Belajar dan berlatih',
            'text' => 'Ikuti roadmap tiga fase berisi modul ringkas, lalu kerjakan latihan praktik di setiap modulnya.',
        ],
        [
            'number' => '04',
            'icon' => 'arrow-trending-up',
            'title' => 'Evaluasi dan bertumbuh',
            'text' => 'Gunakan skor, catatan kekuatan, dan hal yang perlu diperbaiki dari setiap latihan untuk menyempurnakan persiapanmu.',
        ],
    ];
@endphp

<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    class="scroll-smooth motion-reduce:scroll-auto"
>
    <head>
        @include('partials.head')

        <script>
            /*
             * The scroll reveal only hides content when it can reliably bring it
             * back: IntersectionObserver present and no reduced-motion request.
             * Without this class the page is simply fully visible.
             */
            (function () {
                if (! ('IntersectionObserver' in window)) {
                    return;
                }

                if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                    return;
                }

                document.documentElement.classList.add('js');
            })();
        </script>
    </head>
    <body class="min-h-screen bg-warm-cream text-zinc-800 antialiased dark:bg-zinc-950 dark:text-zinc-100">
        {{-- Navbar --}}
        <header
            x-data="{ scrolled: false, open: false }"
            @scroll.window.throttle.120ms="scrolled = window.scrollY > 16"
            @keydown.escape.window="open = false"
            :class="scrolled || open
                ? 'border-brand-teal/12 bg-warm-cream/90 backdrop-blur-xl dark:border-white/10 dark:bg-zinc-950/85'
                : 'border-transparent bg-transparent'"
            class="sticky top-0 z-50 border-b transition-colors duration-300"
        >
            <div class="mx-auto flex h-16 w-full max-w-6xl items-center justify-between gap-4 px-4 sm:h-18 sm:px-6">
                <x-brand.app-logo href="{{ route('home') }}" class="me-0" />

                <nav
                    x-data="{ active: '' }"
                    x-init="
                        if ('IntersectionObserver' in window) {
                            const observer = new IntersectionObserver((entries) => {
                                entries.forEach((entry) => {
                                    if (entry.isIntersecting) active = entry.target.id;
                                });
                            }, { rootMargin: '-45% 0px -50% 0px' });

                            ['manfaat', 'cara-kerja', 'cek-kesiapan', 'persiapan'].forEach((id) => {
                                const section = document.getElementById(id);

                                if (section) observer.observe(section);
                            });
                        }
                    "
                    class="hidden items-center gap-1 md:flex"
                    aria-label="{{ __('Navigasi utama') }}"
                >
                    @foreach ($navLinks as $link)
                        @php $target = ltrim($link['href'], '#'); @endphp
                        <a
                            href="{{ $link['href'] }}"
                            :class="active === '{{ $target }}' ? 'text-brand-teal dark:text-brand-amber' : 'text-zinc-600 dark:text-zinc-300'"
                            class="group relative rounded-lg px-3 py-2 text-sm font-semibold transition hover:text-brand-teal dark:hover:text-brand-amber"
                        >
                            {{ __($link['label']) }}
                            <span
                                :class="active === '{{ $target }}' ? 'scale-x-100' : 'scale-x-0 group-hover:scale-x-100'"
                                class="absolute inset-x-3 -bottom-0.5 h-0.5 origin-left rounded-full bg-brand-orange transition-transform duration-300"
                                aria-hidden="true"
                            ></span>
                        </a>
                    @endforeach
                </nav>

                <div class="flex items-center gap-2 sm:gap-3">
                    @auth
                        {{-- The wrapper carries the responsive display utility: a `hidden`
                             class on the button itself would lose to its own `inline-flex`. --}}
                        <span class="hidden sm:flex">
                            <x-actions.link-button :href="route('dashboard')" wire:navigate size="sm" iconTrailing="arrow-right">
                                {{ __('Dashboard') }}
                            </x-actions.link-button>
                        </span>
                    @else
                        <a
                            href="{{ route('login') }}"
                            class="hidden text-sm font-semibold text-zinc-600 transition hover:text-brand-teal sm:inline dark:text-zinc-300 dark:hover:text-brand-amber"
                        >
                            {{ __('Masuk') }}
                        </a>

                        {{-- Below sm the same call to action moves into the menu panel, so the
                             header never squeezes three controls onto a 320px screen. --}}
                        <span class="hidden sm:flex">
                            <x-actions.link-button :href="route('register')" size="sm" iconTrailing="arrow-right">
                                {{ __('Mulai Sekarang') }}
                            </x-actions.link-button>
                        </span>
                    @endauth

                    <button
                        type="button"
                        @click="open = ! open"
                        :aria-expanded="open ? 'true' : 'false'"
                        aria-controls="landing-mobile-menu"
                        class="inline-flex size-10 items-center justify-center rounded-xl border border-brand-teal/20 bg-white/70 text-brand-teal transition hover:border-brand-teal/50 md:hidden dark:border-white/15 dark:bg-white/5 dark:text-brand-amber"
                    >
                        <span class="sr-only">{{ __('Buka menu navigasi') }}</span>
                        <x-icons.app-icon x-show="! open" name="bars-3" class="size-5" />
                        <x-icons.app-icon x-show="open" x-cloak name="x-mark" class="size-5" />
                    </button>
                </div>
            </div>

            <div
                id="landing-mobile-menu"
                x-show="open"
                x-cloak
                x-transition.opacity.duration.200ms
                class="max-h-[calc(100dvh-4rem)] overflow-y-auto overscroll-contain border-t border-brand-teal/12 bg-warm-cream/95 backdrop-blur-xl md:hidden dark:border-white/10 dark:bg-zinc-950/95"
            >
                <nav class="mx-auto flex w-full max-w-6xl flex-col gap-1 px-4 py-4 sm:px-6" aria-label="{{ __('Navigasi seluler') }}">
                    @foreach ($navLinks as $link)
                        <a
                            href="{{ $link['href'] }}"
                            @click="open = false"
                            class="rounded-xl px-4 py-3 text-base font-semibold text-zinc-700 transition hover:bg-brand-amber/15 hover:text-brand-teal dark:text-zinc-200 dark:hover:text-brand-amber"
                        >
                            {{ __($link['label']) }}
                        </a>
                    @endforeach

                    <div class="mt-2 flex flex-col gap-2 border-t border-brand-teal/12 pt-3 sm:hidden dark:border-white/10">
                        @auth
                            <x-actions.link-button :href="route('dashboard')" wire:navigate iconTrailing="arrow-right" class="w-full">
                                {{ __('Dashboard') }}
                            </x-actions.link-button>
                        @else
                            <x-actions.link-button :href="route('register')" iconTrailing="arrow-right" class="w-full">
                                {{ __('Mulai Sekarang') }}
                            </x-actions.link-button>

                            <a
                                href="{{ route('login') }}"
                                @click="open = false"
                                class="rounded-xl px-4 py-3 text-center text-sm font-semibold text-zinc-700 transition hover:bg-brand-amber/15 hover:text-brand-teal dark:text-zinc-200"
                            >
                                {{ __('Masuk') }}
                            </a>
                        @endauth
                    </div>
                </nav>
            </div>
        </header>

        <main>
            {{-- Hero --}}
            <section class="relative overflow-hidden bg-linear-165 from-brand-amber/30 via-warm-cream to-brand-teal/15 pt-10 pb-16 sm:pt-16 sm:pb-24 dark:from-brand-amber/12 dark:via-zinc-950 dark:to-brand-teal/15">
                <div class="sk-drift pointer-events-none absolute -top-32 -right-20 size-[26rem] rounded-full bg-brand-amber/35 blur-3xl dark:bg-brand-amber/15" aria-hidden="true"></div>
                <div class="sk-drift sk-drift--late pointer-events-none absolute -bottom-40 -left-28 size-[24rem] rounded-full bg-brand-teal/20 blur-3xl dark:bg-brand-teal/20" aria-hidden="true"></div>

                <div class="relative mx-auto grid w-full max-w-6xl gap-14 px-4 sm:px-6 lg:grid-cols-[minmax(0,1.02fr)_minmax(0,0.98fr)] lg:items-center lg:gap-16">
                    <div>
                        <span class="inline-flex items-center gap-2 rounded-full border border-brand-teal/20 bg-white/70 px-4 py-2 text-[0.68rem] font-bold tracking-[0.2em] text-brand-teal uppercase backdrop-blur dark:border-white/10 dark:bg-white/5 dark:text-brand-teal-light">
                            <span class="size-1.5 rounded-full bg-brand-orange" aria-hidden="true"></span>
                            {{ __('Perjalanan beasiswamu dimulai di sini') }}
                        </span>

                        <h1 class="mt-6 text-[2.1rem] leading-[1.05] font-extrabold tracking-tight text-zinc-900 sm:text-[3.3rem] lg:text-[4rem] dark:text-white">
                            {{ __('Siapkan Dirimu.') }}
                            <span class="block">{{ __('Kejar Beasiswamu.') }}</span>

                            {{-- Single animated focal point: one rotating phrase per cycle.
                                 Every phrase shares one grid cell, so the box always sizes to
                                 the tallest phrase and a wrapped phrase is never clipped. --}}
                            {{-- Full-width grid cells let a long phrase wrap inside the
                                 viewport instead of being clipped by the animation mask. --}}
                            <span class="mt-1 grid items-center overflow-hidden">
                                @foreach ($heroRotatingWords as $word)
                                    <span class="sk-word col-start-1 row-start-1 w-full bg-linear-135 from-brand-gold via-brand-orange to-brand-amber bg-clip-text font-extrabold text-transparent">
                                        {{ __($word) }}
                                    </span>
                                @endforeach
                            </span>
                        </h1>

                        <p class="mt-6 max-w-xl text-base leading-relaxed text-zinc-600 sm:text-lg dark:text-zinc-300">
                            {{ __('Temukan beasiswa yang relevan, susun roadmap persiapan, dan kembangkan kesiapanmu melalui pembelajaran yang lebih terarah.') }}
                        </p>

                        <div class="mt-8 flex flex-wrap items-center gap-3">
                            <x-actions.link-button :href="route('register')" size="lg" iconTrailing="arrow-right">
                                {{ __('Mulai Perjalananmu') }}
                            </x-actions.link-button>

                            <a
                                href="#cek-kesiapan"
                                class="inline-flex h-12 items-center gap-2 rounded-xl border border-brand-teal/25 bg-white/70 px-6 text-base font-semibold text-brand-teal backdrop-blur transition hover:border-brand-teal hover:bg-white dark:border-white/15 dark:bg-white/5 dark:text-brand-teal-light dark:hover:border-brand-teal-light"
                            >
                                <x-icons.app-icon name="bolt" class="size-4" />
                                {{ __('Tes Kesiapan Beasiswa') }}
                            </a>
                        </div>

                        <dl class="mt-12 flex flex-wrap items-center gap-x-10 gap-y-6">
                            @foreach ($heroFacts as $fact)
                                <div>
                                    <dt class="text-3xl font-extrabold text-brand-teal tabular-nums dark:text-brand-amber">
                                        {{ $fact['value'] }}
                                    </dt>
                                    <dd class="mt-1 text-xs font-medium tracking-wide text-zinc-500 uppercase dark:text-zinc-400">
                                        {{ __($fact['label']) }}
                                    </dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>

                    {{-- Hero visual: CSS only, no stock imagery. --}}
                    <div class="relative mx-auto w-full max-w-md lg:max-w-none">
                        <div class="sk-float relative rounded-[1.75rem] border border-brand-teal/15 bg-white/90 p-5 shadow-[0_45px_90px_-50px_rgba(39,124,147,0.55)] backdrop-blur sm:p-6 dark:border-white/10 dark:bg-zinc-900/90">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <span class="brand-gradient flex size-10 items-center justify-center rounded-xl text-brand-ink">
                                        <x-icons.app-icon name="academic-cap" class="size-5" />
                                    </span>
                                    <div>
                                        <p class="text-sm font-bold text-zinc-900 dark:text-white">{{ __('Rekomendasi beasiswamu') }}</p>
                                        <p class="text-[0.7rem] font-medium tracking-wide text-zinc-400 uppercase">{{ __('Ilustrasi tampilan') }}</p>
                                    </div>
                                </div>

                                <x-ui.pill variant="brand" icon="sparkles">92%</x-ui.pill>
                            </div>

                            <ul class="mt-5 flex flex-col gap-2.5">
                                @foreach ([
                                    ['name' => 'Beasiswa Unggulan Dalam Negeri', 'meta' => 'S1 · Dalam negeri', 'tier' => 'Sangat sesuai', 'variant' => 'success'],
                                    ['name' => 'Beasiswa Pertukaran Global', 'meta' => 'S1 · Luar negeri', 'tier' => 'Sesuai', 'variant' => 'brand'],
                                ] as $match)
                                    <li class="flex items-center justify-between gap-3 rounded-2xl border border-brand-teal/12 bg-warm-cream/70 px-3.5 py-3 dark:border-white/10 dark:bg-white/5">
                                        <div class="min-w-0">
                                            <p class="line-clamp-2 text-sm leading-snug font-semibold text-zinc-800 dark:text-zinc-100">{{ __($match['name']) }}</p>
                                            <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">{{ __($match['meta']) }}</p>
                                        </div>
                                        <x-ui.pill :variant="$match['variant']">{{ __($match['tier']) }}</x-ui.pill>
                                    </li>
                                @endforeach
                            </ul>

                            <div class="mt-5 border-t border-brand-teal/12 pt-4 dark:border-white/10">
                                <div class="flex items-center justify-between text-xs font-semibold text-zinc-500 dark:text-zinc-400">
                                    <span>{{ __('Progres persiapan') }}</span>
                                    <span class="text-brand-teal tabular-nums dark:text-brand-amber">62%</span>
                                </div>
                                <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-brand-teal/12 dark:bg-white/10">
                                    <div class="brand-gradient h-2 rounded-full" style="width: 62%"></div>
                                </div>
                                <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ __('Roadmap belajar · Fase 2 dari 3') }}
                                </p>
                            </div>
                        </div>

                        <div class="sk-float sk-float--late absolute -top-4 -right-2 hidden items-center gap-2 rounded-2xl border border-brand-teal/15 bg-white px-4 py-3 shadow-xl sm:flex dark:border-white/10 dark:bg-zinc-900">
                            <span class="flex size-9 items-center justify-center rounded-xl bg-brand-amber/25 text-brand-teal dark:text-brand-amber">
                                <x-icons.app-icon name="chat-bubble-left-right" class="size-4" />
                            </span>
                            <div>
                                <p class="text-xs font-bold text-zinc-900 dark:text-white">{{ __('Feedback latihan') }}</p>
                                <p class="text-[0.7rem] text-zinc-500 dark:text-zinc-400">{{ __('Skor rubrik 88') }}</p>
                            </div>
                        </div>

                        <div class="sk-float absolute -bottom-5 -left-2 hidden items-center gap-2 rounded-2xl border border-brand-teal/15 bg-white px-4 py-3 shadow-xl sm:flex dark:border-white/10 dark:bg-zinc-900">
                            <span class="flex size-9 items-center justify-center rounded-xl bg-brand-teal/12 text-brand-teal dark:bg-white/10 dark:text-brand-amber">
                                <x-icons.app-icon name="map" class="size-4" />
                            </span>
                            <div>
                                <p class="text-xs font-bold text-zinc-900 dark:text-white">{{ __('3 fase roadmap') }}</p>
                                <p class="text-[0.7rem] text-zinc-500 dark:text-zinc-400">{{ __('Fondasi · Dokumen · Pendaftaran') }}</p>
                            </div>
                        </div>

                        <x-icons.sparkle class="absolute -bottom-8 right-6 size-8 text-brand-orange/70 sm:size-10" />
                    </div>
                </div>
            </section>

            {{-- Marquee A: value proposition --}}
            <div class="relative border-y border-brand-ink/10 bg-linear-105 from-brand-amber via-brand-gold to-brand-orange py-5 text-brand-ink sm:py-7">
                <div class="flex overflow-hidden">
                    <div class="sk-marquee" style="--sk-marquee-duration: 44s">
                        <ul class="flex shrink-0 items-center">
                            @foreach ($valueMarquee as $phrase)
                                <li class="flex shrink-0 items-center">
                                    <span class="px-6 text-lg font-extrabold tracking-tight whitespace-nowrap uppercase sm:px-10 sm:text-2xl lg:text-3xl">
                                        {{ __($phrase) }}
                                    </span>
                                    <x-icons.sparkle class="size-3.5 shrink-0 opacity-70 sm:size-4" />
                                </li>
                            @endforeach
                        </ul>

                        <ul class="flex shrink-0 items-center" aria-hidden="true">
                            @foreach ($valueMarquee as $phrase)
                                <li class="flex shrink-0 items-center">
                                    <span class="px-6 text-lg font-extrabold tracking-tight whitespace-nowrap uppercase sm:px-10 sm:text-2xl lg:text-3xl">
                                        {{ __($phrase) }}
                                    </span>
                                    <x-icons.sparkle class="size-3.5 shrink-0 opacity-70 sm:size-4" />
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Value proposition --}}
            <section id="manfaat" class="relative scroll-mt-24 overflow-hidden bg-warm-cream py-20 sm:py-28 dark:bg-zinc-950">
                <div class="pointer-events-none absolute top-24 -left-32 size-96 rounded-full bg-brand-amber/15 blur-3xl" aria-hidden="true"></div>

                <div class="relative mx-auto w-full max-w-6xl px-4 sm:px-6">
                    <div class="max-w-3xl" data-reveal>
                        <x-ui.pill variant="brand" icon="sparkles">{{ __('Kenapa Skular') }}</x-ui.pill>

                        <h2 class="mt-5 text-3xl leading-[1.08] font-extrabold tracking-tight text-zinc-900 sm:text-5xl dark:text-white">
                            {{ __('Bukan sekadar daftar beasiswa.') }}
                            <span class="text-brand-teal dark:text-brand-amber">{{ __('Ini ruang latihanmu.') }}</span>
                        </h2>

                        <p class="mt-5 text-base text-zinc-600 sm:text-lg dark:text-zinc-300">
                            {{ __('Lima hal yang Skular kerjakan bersamamu, dari mengenali diri sampai mengevaluasi hasil latihan.') }}
                        </p>
                    </div>

                    <div class="mt-14 grid gap-4 lg:grid-cols-6">
                        {{-- Matching: the widest cell, carries a small visual --}}
                        <article
                            class="card-brand flex flex-col justify-between gap-6 rounded-3xl border border-brand-teal/12 p-6 shadow-sm sm:p-8 lg:col-span-4 dark:border-white/10 dark:bg-zinc-900/60"
                            data-reveal
                        >
                            <div>
                                <span class="brand-gradient flex size-11 items-center justify-center rounded-2xl text-brand-ink">
                                    <x-icons.app-icon name="academic-cap" class="size-5" />
                                </span>

                                <h3 class="mt-5 text-xl font-bold text-zinc-900 sm:text-2xl dark:text-white">
                                    {{ __('Scholarship matching yang menjelaskan alasannya') }}
                                </h3>

                                <p class="mt-3 max-w-xl text-sm leading-relaxed text-zinc-600 dark:text-zinc-300">
                                    {{ __('Skular membandingkan profilmu dengan syarat setiap beasiswa, lalu menampilkan alasan mana yang sudah cocok dan mana yang perlu diperkuat.') }}
                                </p>
                            </div>

                            <ul class="flex flex-wrap gap-2">
                                @foreach (['Jenjang pendidikan', 'Usia', 'IPK', 'Bidang minat', 'Lokasi tujuan', 'Deadline'] as $chip)
                                    <li class="rounded-full border border-brand-teal/15 bg-white/70 px-3 py-1.5 text-xs font-semibold text-brand-teal dark:border-white/10 dark:bg-white/5 dark:text-brand-teal-light">
                                        {{ __($chip) }}
                                    </li>
                                @endforeach
                            </ul>
                        </article>

                        <article
                            class="flex flex-col rounded-3xl border border-brand-teal/12 bg-linear-150 from-brand-amber/25 via-warm-cream to-brand-teal/10 p-6 shadow-sm lg:col-span-2 dark:border-white/10 dark:from-brand-amber/10 dark:via-zinc-900 dark:to-brand-teal/15"
                            data-reveal
                            style="--sk-reveal-delay: 80ms"
                        >
                            <span class="flex size-11 items-center justify-center rounded-2xl bg-white/80 text-brand-teal dark:bg-white/10 dark:text-brand-amber">
                                <x-icons.app-icon name="map" class="size-5" />
                            </span>

                            <h3 class="mt-5 text-xl font-bold text-zinc-900 dark:text-white">{{ __('Learning roadmap') }}</h3>

                            <p class="mt-3 text-sm leading-relaxed text-zinc-600 dark:text-zinc-300">
                                {{ __('Jalur belajar tiga fase yang terbuka bertahap, supaya persiapanmu terstruktur dan tidak melebar.') }}
                            </p>

                            <ol class="mt-6 flex flex-col gap-2 text-xs font-semibold text-zinc-600 dark:text-zinc-300">
                                @foreach (['Fase 1 — Fondasi diri', 'Fase 2 — Menulis dokumen', 'Fase 3 — Persiapan pendaftaran'] as $fase)
                                    <li class="flex items-center gap-2">
                                        <span class="size-1.5 shrink-0 rounded-full bg-brand-orange" aria-hidden="true"></span>
                                        {{ __($fase) }}
                                    </li>
                                @endforeach
                            </ol>
                        </article>

                        <article
                            class="flex flex-col rounded-3xl border border-brand-teal/12 bg-white p-6 shadow-sm lg:col-span-2 dark:border-white/10 dark:bg-zinc-900/60"
                            data-reveal
                            style="--sk-reveal-delay: 160ms"
                        >
                            <span class="flex size-11 items-center justify-center rounded-2xl bg-brand-amber/25 text-brand-teal dark:text-brand-amber">
                                <x-icons.app-icon name="pencil-square" class="size-5" />
                            </span>

                            <h3 class="mt-5 text-xl font-bold text-zinc-900 dark:text-white">{{ __('Latihan terarah') }}</h3>

                            <p class="mt-3 text-sm leading-relaxed text-zinc-600 dark:text-zinc-300">
                                {{ __('Setiap modul punya latihan praktik dengan rubrik sendiri, bukan tugas generik yang bisa dijawab asal.') }}
                            </p>
                        </article>

                        <article
                            class="flex flex-col rounded-3xl bg-brand-ink p-6 text-white shadow-sm lg:col-span-2 dark:bg-zinc-900 dark:ring-1 dark:ring-white/10"
                            data-reveal
                            style="--sk-reveal-delay: 240ms"
                        >
                            <span class="brand-gradient flex size-11 items-center justify-center rounded-2xl text-brand-ink">
                                <x-icons.app-icon name="chat-bubble-left-right" class="size-5" />
                            </span>

                            <h3 class="mt-5 text-xl font-bold">{{ __('Feedback dari AI') }}</h3>

                            <p class="mt-3 text-sm leading-relaxed text-zinc-300">
                                {{ __('AI membaca jawabanmu terhadap rubrik modul, lalu memberi skor, kekuatan, hal yang perlu diperbaiki, dan langkah berikutnya.') }}
                            </p>
                        </article>

                        <article
                            class="flex flex-col justify-between gap-6 rounded-3xl border border-brand-teal/12 bg-brand-teal/8 p-6 shadow-sm lg:col-span-2 dark:border-white/10 dark:bg-brand-teal/12"
                            data-reveal
                            style="--sk-reveal-delay: 320ms"
                        >
                            <div>
                                <span class="flex size-11 items-center justify-center rounded-2xl bg-white/80 text-brand-teal dark:bg-white/10 dark:text-brand-amber">
                                    <x-icons.app-icon name="chart-bar" class="size-5" />
                                </span>

                                <h3 class="mt-5 text-xl font-bold text-zinc-900 dark:text-white">{{ __('Progress tracking') }}</h3>

                                <p class="mt-3 text-sm leading-relaxed text-zinc-600 dark:text-zinc-300">
                                    {{ __('Progres modul, skor latihan, dan status pendaftaran beasiswamu terlihat dalam satu tempat.') }}
                                </p>
                            </div>

                            <div class="h-2 w-full overflow-hidden rounded-full bg-white/70 dark:bg-white/10">
                                <div class="brand-gradient h-2 w-3/5 rounded-full"></div>
                            </div>
                        </article>
                    </div>
                </div>
            </section>

            {{-- Journey --}}
            <section id="cara-kerja" class="relative scroll-mt-24 overflow-hidden bg-warm-sand py-20 sm:py-28 dark:bg-zinc-900/50">
                <div class="relative mx-auto w-full max-w-6xl px-4 sm:px-6">
                    <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between" data-reveal>
                        <div class="max-w-2xl">
                            <x-ui.pill variant="brand" icon="arrows-right-left">{{ __('Cara Kerja') }}</x-ui.pill>

                            <h2 class="mt-5 text-3xl leading-[1.08] font-extrabold tracking-tight text-zinc-900 sm:text-5xl dark:text-white">
                                {{ __('Empat tahap, satu perjalanan.') }}
                            </h2>
                        </div>

                        <p class="max-w-sm text-sm text-zinc-600 sm:text-base dark:text-zinc-300">
                            {{ __('Skular mengikuti alur yang sama seperti yang kamu jalani di dalam aplikasi, dari profiling sampai evaluasi latihan.') }}
                        </p>
                    </div>

                    <ol class="mt-14 grid gap-5 lg:grid-cols-2 lg:gap-x-8">
                        @foreach ($journey as $index => $step)
                            <li
                                class="{{ $index % 2 === 1 ? 'lg:mt-14' : '' }}"
                                data-reveal
                                style="--sk-reveal-delay: {{ $index * 90 }}ms"
                            >
                                <div class="group h-full rounded-3xl border border-brand-teal/12 bg-white/85 p-6 shadow-sm backdrop-blur transition duration-300 hover:-translate-y-1 hover:border-brand-orange/50 hover:shadow-lg sm:p-8 dark:border-white/10 dark:bg-zinc-900/70">
                                    <div class="flex items-start gap-5">
                                        <span class="bg-linear-150 from-brand-amber/40 to-brand-orange/30 flex size-14 shrink-0 items-center justify-center rounded-2xl text-lg font-extrabold text-brand-teal tabular-nums sm:size-16 sm:text-xl dark:text-brand-amber">
                                            {{ $step['number'] }}
                                        </span>

                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2">
                                                <x-icons.app-icon :name="$step['icon']" class="size-4 text-brand-teal dark:text-brand-amber" />
                                                <h3 class="text-lg font-bold text-zinc-900 sm:text-xl dark:text-white">{{ __($step['title']) }}</h3>
                                            </div>

                                            <p class="mt-3 text-sm leading-relaxed text-zinc-600 dark:text-zinc-300">
                                                {{ __($step['text']) }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="mt-6 flex items-center gap-3">
                                        <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-brand-teal/10 dark:bg-white/10">
                                            <div class="brand-gradient h-1.5 rounded-full" style="width: {{ ($index + 1) * 25 }}%"></div>
                                        </div>
                                        <span class="text-[0.7rem] font-bold tracking-wide text-zinc-400 uppercase tabular-nums">
                                            {{ ($index + 1) }}/4
                                        </span>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </section>

            {{-- Profiling Kilat: login-free readiness mini assessment --}}
            <livewire:profiling.quick-assessment />

            {{-- Scholarship preparation preview --}}
            <section id="persiapan" class="relative scroll-mt-24 overflow-hidden bg-warm-cream py-20 sm:py-28 dark:bg-zinc-950">
                <div class="pointer-events-none absolute -right-24 bottom-10 size-80 rounded-full bg-brand-teal/12 blur-3xl" aria-hidden="true"></div>

                <div class="relative mx-auto w-full max-w-6xl px-4 sm:px-6">
                    <div class="max-w-2xl" data-reveal>
                        <x-ui.pill variant="brand" icon="book-open">{{ __('Persiapan') }}</x-ui.pill>

                        <h2 class="mt-5 text-3xl leading-[1.08] font-extrabold tracking-tight text-zinc-900 sm:text-5xl dark:text-white">
                            {{ __('Yang kamu kerjakan setiap hari di Skular.') }}
                        </h2>

                        <p class="mt-5 text-base text-zinc-600 dark:text-zinc-300">
                            {{ __('Belajar satu modul, kerjakan latihannya, lalu lihat perkembangannya. Semua contoh di bawah adalah ilustrasi tampilan.') }}
                        </p>
                    </div>

                    <div class="mt-14 grid gap-5 lg:grid-cols-12">
                        {{-- Roadmap preview --}}
                        <article
                            class="rounded-3xl border border-brand-teal/12 bg-white p-6 shadow-sm sm:p-8 lg:col-span-7 dark:border-white/10 dark:bg-zinc-900/60"
                            data-reveal
                        >
                            <div class="flex items-center justify-between gap-3">
                                <h3 class="text-lg font-bold text-zinc-900 dark:text-white">{{ __('Roadmap belajar') }}</h3>
                                <x-ui.pill icon="sparkles">{{ __('Ilustrasi') }}</x-ui.pill>
                            </div>

                            <ul class="mt-5 flex flex-col gap-2.5">
                                @foreach ([
                                    ['label' => 'Mengenal Tujuan dan Motivasi Beasiswa', 'state' => 'Selesai', 'variant' => 'success', 'icon' => 'check'],
                                    ['label' => 'Menyusun Personal Branding', 'state' => 'Skor 88', 'variant' => 'success', 'icon' => 'star'],
                                    ['label' => 'Anatomi Esai Motivasi', 'state' => 'Sedang berjalan', 'variant' => 'brand', 'icon' => 'book-open'],
                                    ['label' => 'Simulasi Wawancara Beasiswa', 'state' => 'Terkunci', 'variant' => 'neutral', 'icon' => 'clock'],
                                ] as $row)
                                    <li class="flex items-center justify-between gap-3 rounded-2xl border border-brand-teal/10 bg-warm-cream/60 px-4 py-3 dark:border-white/10 dark:bg-white/5">
                                        <span class="flex min-w-0 items-center gap-3">
                                            <span class="flex size-7 shrink-0 items-center justify-center rounded-lg bg-brand-amber/25 text-brand-teal dark:text-brand-amber">
                                                <x-icons.app-icon :name="$row['icon']" class="size-3.5" />
                                            </span>
                                            <span class="line-clamp-2 text-sm leading-snug text-zinc-700 dark:text-zinc-200">{{ __($row['label']) }}</span>
                                        </span>
                                        <x-ui.pill :variant="$row['variant']">{{ __($row['state']) }}</x-ui.pill>
                                    </li>
                                @endforeach
                            </ul>
                        </article>

                        <div class="grid gap-5 lg:col-span-5">
                            {{-- Practice + AI feedback preview --}}
                            <article
                                class="rounded-3xl border border-brand-teal/12 bg-linear-150 from-brand-amber/20 via-white to-brand-teal/8 p-6 shadow-sm dark:border-white/10 dark:from-brand-amber/10 dark:via-zinc-900 dark:to-brand-teal/15"
                                data-reveal
                                style="--sk-reveal-delay: 90ms"
                            >
                                <div class="flex items-center justify-between gap-3">
                                    <h3 class="text-base font-bold text-zinc-900 dark:text-white">{{ __('Feedback latihan') }}</h3>
                                    <x-ui.pill variant="success" icon="star">{{ __('Skor 88') }}</x-ui.pill>
                                </div>

                                <div class="mt-5 flex flex-col gap-4 text-sm">
                                    <div>
                                        <p class="text-[0.7rem] font-bold tracking-wide text-emerald-600 uppercase dark:text-emerald-400">{{ __('Kekuatan') }}</p>
                                        <p class="mt-1 text-zinc-600 dark:text-zinc-300">{{ __('Motivasimu jelas dan didukung contoh nyata saat mengajar sukarela.') }}</p>
                                    </div>

                                    <div>
                                        <p class="text-[0.7rem] font-bold tracking-wide text-amber-600 uppercase dark:text-amber-400">{{ __('Perlu diperbaiki') }}</p>
                                        <p class="mt-1 text-zinc-600 dark:text-zinc-300">{{ __('Rencana belajar belum menyebut mata kuliah atau laboratorium yang ingin kamu ambil.') }}</p>
                                    </div>
                                </div>

                                <p class="mt-5 text-[0.7rem] font-semibold tracking-wide text-zinc-400 uppercase">
                                    {{ __('Ilustrasi tampilan') }}
                                </p>
                            </article>

                            {{-- Progress preview --}}
                            <article
                                class="rounded-3xl bg-brand-ink p-6 text-white shadow-sm dark:bg-zinc-900 dark:ring-1 dark:ring-white/10"
                                data-reveal
                                style="--sk-reveal-delay: 170ms"
                            >
                                <h3 class="text-base font-bold">{{ __('Progres persiapan') }}</h3>

                                <div class="mt-4 flex items-end justify-between gap-4">
                                    <span class="text-4xl font-extrabold text-brand-amber tabular-nums">67%</span>
                                    <span class="text-xs text-zinc-400">{{ __('4 dari 6 modul selesai') }}</span>
                                </div>

                                <div class="mt-4 h-2.5 w-full overflow-hidden rounded-full bg-white/10">
                                    <div class="brand-gradient h-2.5 rounded-full" style="width: 67%"></div>
                                </div>

                                <p class="mt-4 text-[0.7rem] font-semibold tracking-wide text-zinc-500 uppercase">
                                    {{ __('Ilustrasi tampilan') }}
                                </p>
                            </article>
                        </div>
                    </div>
                </div>
            </section>

            {{-- FAQ: only the admin-selected published questions, never the full list. --}}
            <livewire:faq.landing-section />

            {{-- Marquee B: brand message, travelling the other way --}}
            <div class="relative border-y border-brand-ink/20 bg-linear-105 from-brand-teal via-brand-teal-light to-brand-teal py-6 text-white sm:py-8">
                <div class="flex overflow-hidden">
                    <div class="sk-marquee sk-marquee--reverse" style="--sk-marquee-duration: 28s">
                        <ul class="flex shrink-0 items-center">
                            @foreach ($brandMarquee as $word)
                                <li class="flex shrink-0 items-center">
                                    <span class="px-5 text-2xl font-extrabold tracking-tight whitespace-nowrap sm:px-8 sm:text-4xl lg:text-5xl">
                                        {{ __($word) }}
                                    </span>
                                    <x-icons.sparkle class="size-4 shrink-0 text-brand-amber sm:size-5" />
                                </li>
                            @endforeach
                        </ul>

                        <ul class="flex shrink-0 items-center" aria-hidden="true">
                            @foreach ($brandMarquee as $word)
                                <li class="flex shrink-0 items-center">
                                    <span class="px-5 text-2xl font-extrabold tracking-tight whitespace-nowrap sm:px-8 sm:text-4xl lg:text-5xl">
                                        {{ __($word) }}
                                    </span>
                                    <x-icons.sparkle class="size-4 shrink-0 text-brand-amber sm:size-5" />
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Final CTA --}}
            <section id="mulai" class="relative scroll-mt-24 overflow-hidden bg-brand-ink py-20 sm:py-28 dark:bg-zinc-950">
                <div class="brand-grid pointer-events-none absolute inset-0 opacity-10" aria-hidden="true"></div>
                <div class="sk-drift pointer-events-none absolute -top-24 left-1/4 size-96 rounded-full bg-brand-amber/20 blur-3xl" aria-hidden="true"></div>
                <div class="pointer-events-none absolute -bottom-32 right-0 size-80 rounded-full bg-brand-teal/30 blur-3xl" aria-hidden="true"></div>

                <div class="relative mx-auto flex w-full max-w-4xl flex-col items-center px-4 text-center sm:px-6" data-reveal>
                    <x-icons.sparkle class="size-8 text-brand-amber" />

                    <h2 class="mt-6 text-3xl leading-[1.06] font-extrabold tracking-tight text-balance text-white sm:text-5xl">
                        {{ __('Kesempatan Tidak Harus Ditunggu.') }}
                        <span class="text-brand-amber">{{ __('Persiapkan Dirimu.') }}</span>
                    </h2>

                    <p class="mt-6 max-w-xl text-base text-zinc-300">
                        {{ __('Mulai bangun persiapan yang lebih terarah bersama Skular. Gratis, dan hasil profilingmu langsung terlihat.') }}
                    </p>

                    <div class="mt-9 flex flex-wrap items-center justify-center gap-3">
                        @auth
                            <x-actions.link-button :href="route('dashboard')" wire:navigate size="lg" iconTrailing="arrow-right">
                                {{ __('Buka Dashboard') }}
                            </x-actions.link-button>
                        @else
                            <x-actions.link-button :href="route('register')" size="lg" iconTrailing="arrow-right">
                                {{ __('Mulai Sekarang') }}
                            </x-actions.link-button>

                            <x-actions.link-button :href="route('login')" size="lg" variant="outline">
                                {{ __('Masuk ke akunku') }}
                            </x-actions.link-button>
                        @endauth
                    </div>

                    <p class="mt-6 text-xs text-zinc-500">
                        {{ __('Skular membantu persiapanmu. Hasil setiap pendaftaran tetap ditentukan oleh penyelenggara beasiswa.') }}
                    </p>
                </div>
            </section>
        </main>

        {{-- Footer --}}
        <footer class="border-t border-white/10 bg-brand-ink pb-10 dark:bg-zinc-950">
            <div class="mx-auto w-full max-w-6xl px-4 sm:px-6">
                <div class="grid gap-10 py-14 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="lg:col-span-2">
                        <x-brand.app-logo href="{{ route('home') }}" class="me-0" />

                        <p class="mt-5 max-w-sm text-sm leading-relaxed text-zinc-400">
                            {{ __('Platform persiapan beasiswa berbasis AI: profiling, matching beasiswa, roadmap belajar, dan latihan berskor.') }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-bold tracking-[0.18em] text-zinc-500 uppercase">{{ __('Jelajahi') }}</p>

                        <ul class="mt-4 flex flex-col gap-3">
                            @foreach ($navLinks as $link)
                                <li>
                                    <a href="{{ $link['href'] }}" class="text-sm text-zinc-300 transition hover:text-brand-amber">
                                        {{ __($link['label']) }}
                                    </a>
                                </li>
                            @endforeach

                            <li>
                                <a href="{{ route('faq.index') }}" class="text-sm text-zinc-300 transition hover:text-brand-amber">
                                    {{ __('FAQ') }}
                                </a>
                            </li>
                        </ul>
                    </div>

                    <div>
                        <p class="text-xs font-bold tracking-[0.18em] text-zinc-500 uppercase">{{ __('Akun') }}</p>

                        <ul class="mt-4 flex flex-col gap-3">
                            @auth
                                <li>
                                    <a href="{{ route('dashboard') }}" wire:navigate class="text-sm text-zinc-300 transition hover:text-brand-amber">
                                        {{ __('Dashboard') }}
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('profiling') }}" wire:navigate class="text-sm text-zinc-300 transition hover:text-brand-amber">
                                        {{ __('Profiling') }}
                                    </a>
                                </li>
                            @else
                                <li>
                                    <a href="{{ route('register') }}" class="text-sm text-zinc-300 transition hover:text-brand-amber">
                                        {{ __('Daftar akun') }}
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('login') }}" class="text-sm text-zinc-300 transition hover:text-brand-amber">
                                        {{ __('Masuk') }}
                                    </a>
                                </li>
                            @endauth
                        </ul>
                    </div>
                </div>

                <div class="flex flex-col items-center justify-between gap-3 border-t border-white/10 pt-6 text-xs text-zinc-500 sm:flex-row">
                    <p>&copy; {{ now()->year }} {{ config('app.name', 'Skular') }}. {{ __('Seluruh hak cipta dilindungi.') }}</p>
                    <p>{{ __('Dibuat untuk pelajar dan mahasiswa Indonesia.') }}</p>
                </div>
            </div>
        </footer>
    </body>
</html>
