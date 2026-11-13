{{-- Public landing page (route home), claymorphism style: hero with rotating phrase, the two core features, how it works, Profiling Kilat, principles, FAQ and the final call to action. Adds the js class on <html> that enables the scroll reveal in app.js. --}}
@php
    /**
     * Landing page copy.
     *
     * Every statement describes something that exists in the app today
     * (8-step profiling, up to 5 scholarships, module steps, AI feedback shown
     * as labels, application tracker). No numbers, ratings, testimonials or
     * user counts are invented, and mentees are only ever shown labels.
     */
    $navLinks = [
        ['href' => '#manfaat', 'label' => 'Fitur'],
        ['href' => '#cara-kerja', 'label' => 'Cara Kerja'],
        ['href' => '#cek-kesiapan', 'label' => 'Cek Kesiapan'],
        ['href' => '#persiapan', 'label' => 'Prinsip'],
    ];

    /*
     * Rotating hero phrases. "Lebih besar peluang lolos" is deliberately left
     * out: Skular has no data that proves it, and the landing page makes no
     * claims about selection results.
     */
    $heroRotatingWords = ['Lebih Banyak.', 'Lebih Cepat.', 'Lebih Pede.'];

    /** Pastel clay tints, cycled through by index wherever a list needs colour. */
    $tints = ['bg-brand-teal-soft', 'bg-brand-amber-soft', 'bg-brand-peach-soft', 'bg-brand-mint-soft', 'bg-brand-rose-soft'];

    $factStrip = [
        'Profiling 8 langkah',
        'Rekomendasi beasiswa sesuai profilmu',
        'Pilih sampai 5 beasiswa',
        'Modul belajar bertahap',
        'Feedback latihan berupa label',
        'Tracker pendaftaran beasiswa',
    ];

    $heroScholarships = [
        ['name' => 'Beasiswa S1 dalam negeri', 'match' => 'Sangat Cocok', 'status' => 'Persiapan', 'status_class' => 'bg-brand-teal-soft text-brand-teal-deep'],
        ['name' => 'Beasiswa prestasi mahasiswa aktif', 'match' => 'Cocok', 'status' => 'Dipilih', 'status_class' => 'bg-brand-rose-soft text-brand-ink'],
        ['name' => 'Beasiswa pertukaran pelajar', 'match' => 'Cocok', 'status' => 'Sudah Submit', 'status_class' => 'bg-brand-amber-soft text-brand-ink'],
    ];

    $moduleSteps = [
        ['label' => 'Pembukaan', 'state' => 'done'],
        ['label' => 'Pahami', 'state' => 'done'],
        ['label' => 'Quick Question', 'state' => 'done'],
        ['label' => 'Praktik', 'state' => 'current'],
        ['label' => 'Checkpoint', 'state' => 'locked'],
    ];

    $journey = [
        [
            'icon' => 'identification',
            'title' => 'Isi profiling',
            'text' => 'Delapan pertanyaan singkat tentang jenjang, usia, IPK, dan rencana studimu. Profil ini bisa kamu ubah lagi nanti.',
            'tint' => 'bg-brand-peach-soft',
        ],
        [
            'icon' => 'academic-cap',
            'title' => 'Pilih beasiswa',
            'text' => 'Skular menampilkan beasiswa yang cocok beserta alasannya. Kamu memilih sampai 5 beasiswa dan satu beasiswa utama.',
            'tint' => 'bg-brand-mint-soft',
        ],
        [
            'icon' => 'map',
            'title' => 'Ikuti roadmap',
            'text' => 'Kerjakan modul satu per satu: baca materi, jawab Quick Question, lalu tulis jawaban praktikmu sendiri dan baca feedbacknya.',
            'tint' => 'bg-brand-teal-soft',
        ],
        [
            'icon' => 'paper-airplane',
            'title' => 'Daftar dan laporkan',
            'text' => 'Daftar di situs resmi penyelenggara, unggah bukti pendaftaranmu di Beasiswa Saya, lalu laporkan hasilnya setelah pengumuman.',
            'tint' => 'bg-brand-rose-soft',
        ],
    ];

    $labelTints = [
        \App\Enums\AiScoreLabel::SangatBaik->value => 'bg-brand-mint-soft',
        \App\Enums\AiScoreLabel::Baik->value => 'bg-brand-teal-soft',
        \App\Enums\AiScoreLabel::Cukup->value => 'bg-brand-amber-soft',
        \App\Enums\AiScoreLabel::PerluDiperbaiki->value => 'bg-brand-rose-soft',
    ];

    $scoreLabels = collect(\App\Enums\AiScoreLabel::cases())
        ->map(fn (\App\Enums\AiScoreLabel $label): array => [
            'label' => $label->label(),
            'hint' => $label->hint(),
            'tint' => $labelTints[$label->value],
        ])
        ->all();

    $principles = [
        [
            'icon' => 'pencil-square',
            'title' => 'Jawabanmu tetap tulisanmu',
            'text' => 'AI di Skular menjelaskan materi, menilai latihan, dan memberi saran. AI tidak menuliskan jawaban akhir untukmu.',
            'tint' => 'bg-brand-teal-soft',
        ],
        [
            'icon' => 'folder-open',
            'title' => 'Semua tercatat rapi',
            'text' => 'Jawaban latihan disimpan dulu sebelum dinilai. Progres modul dan status setiap beasiswa ada di satu dashboard, jadi mudah kamu tunjukkan ke orangtua.',
            'tint' => 'bg-brand-amber-soft',
        ],
        [
            'icon' => 'arrow-path',
            'title' => 'Materi terus diperbaiki',
            'text' => 'Di akhir setiap modul ada kolom masukan. Masukan itu kami pakai untuk memperbaiki materi berikutnya.',
            'tint' => 'bg-brand-mint-soft',
        ],
        [
            'icon' => 'bolt',
            'title' => 'Langsung ke langkah berikutnya',
            'text' => 'Dashboard selalu menunjukkan satu hal paling penting yang perlu kamu kerjakan sekarang.',
            'tint' => 'bg-brand-peach-soft',
        ],
        [
            'icon' => 'scale',
            'title' => 'Tidak ada janji lolos',
            'text' => 'Hasil seleksi ditentukan penyelenggara beasiswa. Skular membantu persiapanmu, bukan menentukan hasilnya.',
            'tint' => 'bg-brand-rose-soft',
        ],
    ];

    $disclaimer = 'Skular adalah platform persiapan beasiswa dan tidak berafiliasi dengan penyelenggara beasiswa mana pun. Pendaftaran beasiswa selalu gratis dan dilakukan di situs resmi penyelenggaranya.';

    /*
     * JSON-LD contains only facts that exist in this codebase: the brand, the
     * public URL and the logo. No ratings, awards, addresses or success
     * statistics are invented.
     */
    $homeUrl = \App\Support\Seo::url('/');

    $structuredData = [
        [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            '@id' => $homeUrl.'#organization',
            'name' => 'Skular',
            'url' => $homeUrl,
            'logo' => \App\Support\Seo::url('/skular-logo.png'),
            'description' => config('seo.defaults.description'),
        ],
        [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            '@id' => $homeUrl.'#website',
            'name' => 'Skular',
            'url' => $homeUrl,
            'description' => config('seo.defaults.description'),
            'inLanguage' => 'id-ID',
            'publisher' => ['@id' => $homeUrl.'#organization'],
        ],
    ];
@endphp

<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    class="scroll-smooth motion-reduce:scroll-auto"
>
    <head>
        @include('partials.head', ['structuredData' => $structuredData])

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
    <body class="sk-landing min-h-screen overflow-x-clip bg-warm-cream text-brand-ink antialiased">
        <a
            href="#konten"
            class="sr-only z-[100] min-h-11 rounded-xl bg-brand-ink px-4 py-3 text-sm font-semibold text-white focus:not-sr-only focus:fixed focus:start-4 focus:top-4"
        >
            {{ __('Lewati ke konten utama') }}
        </a>

        {{-- Navbar: a floating clay bar --}}
        <header
            x-data="{ open: false }"
            @keydown.escape.window="open = false"
            class="sticky top-0 z-50 px-3 pt-3 sm:px-6"
        >
            <div class="clay-sm mx-auto flex h-16 w-full max-w-6xl items-center justify-between gap-3 rounded-[1.5rem] bg-white/95 ps-3 pe-2 sm:ps-5">
                <x-brand.app-logo href="{{ route('home') }}" class="me-0 min-h-11 rounded-lg" />

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
                            :class="active === '{{ $target }}' ? 'bg-brand-amber-soft text-brand-ink' : 'text-zinc-700 hover:bg-brand-cream'"
                            class="flex min-h-11 items-center rounded-xl px-3.5 text-sm font-semibold transition-colors"
                        >
                            {{ __($link['label']) }}
                        </a>
                    @endforeach
                </nav>

                <div class="flex items-center gap-2">
                    @auth
                        {{-- The wrapper carries the responsive display utility so the
                             component's own inline-flex never overrides `hidden`. --}}
                        <span class="hidden sm:flex">
                            <x-landing.cta :href="route('dashboard')" wire:navigate iconTrailing="arrow-right">
                                {{ __('Dashboard') }}
                            </x-landing.cta>
                        </span>
                    @else
                        <a
                            href="{{ route('login') }}"
                            class="hidden min-h-11 items-center rounded-xl px-3 text-sm font-semibold text-zinc-700 transition-colors hover:bg-brand-cream sm:flex"
                        >
                            {{ __('Masuk') }}
                        </a>

                        {{-- Below sm the same call to action moves into the menu panel, so the
                             header never squeezes three controls onto a 320px screen. --}}
                        <span class="hidden sm:flex">
                            <x-landing.cta :href="route('register')">
                                {{ __('Mulai Sekarang') }}
                            </x-landing.cta>
                        </span>
                    @endauth

                    <button
                        type="button"
                        @click="open = ! open"
                        :aria-expanded="open ? 'true' : 'false'"
                        aria-expanded="false"
                        aria-controls="landing-mobile-menu"
                        class="clay-sm clay-press inline-flex size-11 items-center justify-center rounded-2xl bg-brand-amber-soft text-brand-ink md:hidden"
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
                x-transition.opacity.duration.150ms
                class="clay mx-auto mt-2 max-h-[calc(100dvh-6rem)] w-full max-w-6xl overflow-y-auto overscroll-contain rounded-[1.5rem] bg-white md:hidden"
            >
                <nav class="flex flex-col gap-1 p-3" aria-label="{{ __('Navigasi seluler') }}">
                    @foreach ($navLinks as $link)
                        <a
                            href="{{ $link['href'] }}"
                            @click="open = false"
                            class="flex min-h-11 items-center rounded-xl px-4 text-base font-semibold text-zinc-800 transition-colors hover:bg-brand-amber-soft"
                        >
                            {{ __($link['label']) }}
                        </a>
                    @endforeach

                    <div class="mt-2 flex flex-col gap-3 border-t border-brand-ink/10 px-1 pt-4 pb-1 sm:hidden">
                        @auth
                            <x-landing.cta :href="route('dashboard')" wire:navigate iconTrailing="arrow-right" class="w-full">
                                {{ __('Dashboard') }}
                            </x-landing.cta>
                        @else
                            <x-landing.cta :href="route('register')" class="w-full">
                                {{ __('Mulai Sekarang') }}
                            </x-landing.cta>

                            <x-landing.cta :href="route('login')" variant="quiet" class="w-full">
                                {{ __('Masuk') }}
                            </x-landing.cta>
                        @endauth
                    </div>
                </nav>
            </div>
        </header>

        <main id="konten">
            {{-- Hero --}}
            {{-- Pulled up under the floating navbar so the colour runs to the top edge. --}}
            <section class="hero-mesh relative -mt-[4.75rem] overflow-hidden pt-[7.75rem] pb-20 sm:pt-[8.75rem] sm:pb-28" aria-labelledby="hero-heading">
                {{-- Clay shapes: pure decoration, hidden from assistive tech. --}}
                <div class="pointer-events-none absolute inset-0 hidden sm:block" aria-hidden="true">
                    <span class="clay sk-float absolute top-16 right-[6%] size-16 rounded-full bg-brand-amber"></span>
                    <span class="clay sk-float sk-float--late absolute bottom-16 left-[4%] size-12 rounded-2xl bg-brand-teal-light"></span>
                    <span class="clay sk-float absolute right-[44%] bottom-10 size-9 rounded-full bg-brand-peach"></span>
                </div>

                <div class="relative mx-auto grid w-full max-w-6xl gap-14 px-4 sm:px-6 lg:grid-cols-12 lg:items-center lg:gap-10">
                    <div class="lg:col-span-7">
                        <p class="sk-fade-up clay-sm inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-xs font-semibold text-brand-teal-deep sm:text-sm">
                            <span class="size-2 rounded-full bg-brand-orange" aria-hidden="true"></span>
                            {{ __('Teman perjalanan belajar setiap pelajar Indonesia') }}
                        </p>

                        {{-- The two lines rise in once; the third line cycles through the phrases.
                             Screen readers get the full sentence once from the sr-only copy. --}}
                        <h1 id="hero-heading" class="mt-6 text-[2.5rem] leading-[1.04] font-extrabold tracking-tight sm:text-6xl lg:text-[4.25rem]">
                            <span class="block"><span class="sk-word-in" style="--i: 0">{{ __('Banyak Beasiswa.') }}</span></span>
                            <span class="block"><span class="sk-word-in" style="--i: 2">{{ __('Dalam Satu Website.') }}</span></span>
                            <span class="sr-only">{{ __('Lebih banyak, lebih cepat, lebih pede.') }}</span>
                            <span class="mt-3 block" aria-hidden="true">
                                <span class="sk-rotator clay rounded-[1.5rem] bg-brand-amber px-4 pt-1 pb-2 sm:px-5">
                                    @foreach ($heroRotatingWords as $word)
                                        <span class="sk-rotator__word">{{ __($word) }}</span>
                                    @endforeach
                                </span>
                            </span>
                        </h1>

                        <p class="sk-fade-up mt-7 max-w-xl text-base leading-relaxed text-zinc-700 sm:text-lg">
                            {{ __('Dari bingung sampai submit, untuk beberapa beasiswa sekaligus. Skular mencarikan beasiswa yang cocok dengan profilmu, lalu memandu langkah daftarnya satu per satu.') }}
                        </p>

                        <div class="sk-fade-up sk-fade-up--late mt-8 flex flex-wrap items-center gap-x-4 gap-y-3">
                            @auth
                                <x-landing.cta :href="route('dashboard')" wire:navigate size="lg" iconTrailing="arrow-right">
                                    {{ __('Buka Dashboard') }}
                                </x-landing.cta>
                            @else
                                <x-landing.cta :href="route('register')" size="lg" iconTrailing="arrow-right">
                                    {{ __('Mulai Sekarang') }}
                                </x-landing.cta>
                            @endauth

                            <x-landing.cta href="#cara-kerja" size="lg" variant="quiet">
                                {{ __('Lihat cara kerjanya') }}
                            </x-landing.cta>
                        </div>

                        <p class="mt-6 max-w-md text-sm leading-relaxed text-zinc-700">
                            {{ __('Daftar akun Skular gratis. Pendaftaran beasiswanya sendiri selalu gratis di situs resmi penyelenggara.') }}
                        </p>
                    </div>

                    {{-- Hero visual: a clay replica of the "Beasiswa Saya" list, clearly marked as an illustration. --}}
                    <figure class="lg:col-span-5">
                        <div class="relative">
                        <div class="clay rounded-[2rem] bg-white p-5 sm:p-6">
                            <div class="flex items-center justify-between gap-3">
                                <p class="font-bold">{{ __('Beasiswa pilihanmu') }}</p>
                                <p class="rounded-full bg-brand-cream px-3 py-1 text-xs font-semibold text-zinc-700">{{ __('maksimal 5') }}</p>
                            </div>

                            <ul class="mt-4 flex flex-col gap-3">
                                @foreach ($heroScholarships as $item)
                                    <li class="flex flex-col gap-2 rounded-2xl bg-brand-cream px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold">{{ __($item['name']) }}</p>
                                            <p class="mt-0.5 text-xs font-medium text-brand-teal-deep">{{ __($item['match']) }}</p>
                                        </div>
                                        <span class="w-fit shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold {{ $item['status_class'] }}">
                                            {{ __($item['status']) }}
                                        </span>
                                    </li>
                                @endforeach
                            </ul>

                            <div class="clay-sm mt-4 rounded-2xl bg-brand-teal-deep px-4 py-3 text-white">
                                <p class="text-xs font-medium text-brand-teal-soft">{{ __('Langkah berikutnya') }}</p>
                                <p class="mt-1 text-sm font-semibold">{{ __('Tulis draf esai motivasi di modul Praktik') }}</p>
                            </div>
                        </div>

                        <div class="clay-sm sk-float absolute -top-5 -left-4 hidden items-center gap-2 rounded-2xl bg-brand-mint-soft px-3.5 py-2.5 text-sm font-semibold sm:flex" aria-hidden="true">
                            <x-icons.app-icon name="check-circle" class="size-5 text-emerald-700" />
                            {{ __('Sangat Cocok') }}
                        </div>

                        <div class="clay-sm sk-float sk-float--late absolute -right-3 -bottom-6 hidden items-center gap-2 rounded-2xl bg-brand-peach-soft px-3.5 py-2.5 text-sm font-semibold sm:flex" aria-hidden="true">
                            <x-icons.app-icon name="paper-airplane" class="size-5 text-brand-ink" />
                            {{ __('Sudah Submit') }}
                        </div>
                        </div>

                        <figcaption class="mt-4 text-xs text-zinc-700 sm:mt-9">
                            {{ __('Ilustrasi tampilan, bukan data pengguna.') }}
                        </figcaption>
                    </figure>
                </div>
            </section>

            {{-- Fact strip: only things that exist in the app. Two identical tracks make the loop seamless. --}}
            <div class="sk-marquee-band py-4 sm:py-5">
                <div class="sk-marquee-frame">
                    <div class="sk-marquee" style="--sk-marquee-duration: 55s">
                        @foreach ([false, true] as $is_duplicate)
                            <ul class="sk-marquee__track flex shrink-0 items-center gap-3 py-2 pe-3" @if ($is_duplicate) aria-hidden="true" @endif>
                                @foreach ($factStrip as $index => $fact)
                                    <li class="clay-sm {{ $tints[$index % count($tints)] }} flex shrink-0 items-center gap-2.5 rounded-full px-4 py-2 text-sm font-semibold whitespace-nowrap sm:px-5 sm:py-2.5 sm:text-base">
                                        <x-icons.app-icon name="check-badge" class="size-5 text-brand-teal-deep" />
                                        {{ __($fact) }}
                                    </li>
                                @endforeach
                            </ul>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- The two core features --}}
            <section id="manfaat" class="scroll-mt-28 py-20 sm:py-28" aria-labelledby="manfaat-heading">
                <div class="mx-auto w-full max-w-6xl px-4 sm:px-6">
                    <div class="max-w-2xl" data-reveal>
                        <p class="clay-sm inline-flex rounded-full bg-brand-teal-soft px-4 py-1.5 text-sm font-semibold text-brand-teal-deep">{{ __('Dua fitur utama') }}</p>
                        <h2 id="manfaat-heading" class="mt-4 text-3xl leading-tight font-extrabold tracking-tight sm:text-5xl">
                            {{ __('Satu kali persiapan untuk banyak beasiswa.') }}
                        </h2>
                        <p class="mt-4 text-base leading-relaxed text-zinc-700 sm:text-lg">
                            {{ __('Banyak beasiswa meminta hal yang mirip: esai, CV, dan wawancara. Di Skular kamu menyiapkannya sekali, lalu menyesuaikannya untuk setiap beasiswa yang kamu pilih.') }}
                        </p>
                    </div>

                    {{-- Feature 1: Informasi Beasiswa --}}
                    <div class="clay mt-14 grid gap-10 rounded-[2.5rem] bg-brand-teal-soft p-6 sm:p-10 lg:grid-cols-2 lg:items-center lg:gap-12" data-reveal>
                        <div>
                            <span class="clay-sm flex size-14 items-center justify-center rounded-2xl bg-white text-xl font-extrabold text-brand-teal-deep">01</span>
                            <h3 class="mt-5 text-2xl font-extrabold tracking-tight sm:text-3xl">{{ __('Informasi Beasiswa') }}</h3>
                            <p class="mt-4 leading-relaxed text-zinc-800">
                                {{ __('Isi profiling sekali. Skular mencocokkan profilmu dengan syarat setiap beasiswa yang terbuka, lalu menjelaskan bagian mana yang sudah cocok dan mana yang perlu kamu cek lagi.') }}
                            </p>
                            <ul class="mt-6 flex flex-col gap-3 text-sm font-medium">
                                @foreach ([
                                    'Label kecocokan: Sangat Cocok, Cocok, atau Kurang Cocok',
                                    'Alasan kecocokan ditulis terbuka di setiap beasiswa',
                                    'Tautan ke situs resmi penyelenggara untuk cek ulang syarat',
                                ] as $point)
                                    <li class="flex items-start gap-3">
                                        <span class="clay-sm flex size-7 shrink-0 items-center justify-center rounded-lg bg-white">
                                            <x-icons.app-icon name="check" class="size-4 text-brand-teal-deep" />
                                        </span>
                                        <span class="pt-1">{{ __($point) }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        <figure>
                            <div class="clay rounded-[2rem] bg-white p-5 sm:p-6">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <p class="font-bold">{{ __('Beasiswa S1 dalam negeri') }}</p>
                                        <p class="mt-1 text-xs text-zinc-700">{{ __('Jenjang S1 · Dalam negeri') }}</p>
                                    </div>
                                    <span class="rounded-full bg-brand-mint-soft px-3 py-1 text-xs font-semibold text-emerald-800">{{ __('Sangat Cocok') }}</span>
                                </div>

                                <p class="mt-5 text-xs font-semibold text-zinc-700">{{ __('Kenapa cocok') }}</p>
                                <ul class="mt-2 flex flex-col gap-2 text-sm">
                                    <li class="flex gap-2 rounded-xl bg-brand-cream px-3 py-2"><x-icons.app-icon name="check-circle" class="mt-0.5 size-4 text-emerald-700" /><span>{{ __('Jenjang pendidikanmu sesuai syarat') }}</span></li>
                                    <li class="flex gap-2 rounded-xl bg-brand-cream px-3 py-2"><x-icons.app-icon name="check-circle" class="mt-0.5 size-4 text-emerald-700" /><span>{{ __('Usiamu masih dalam batas pendaftaran') }}</span></li>
                                    <li class="flex gap-2 rounded-xl bg-brand-amber-soft px-3 py-2"><x-icons.app-icon name="exclamation-circle" class="mt-0.5 size-4 text-amber-800" /><span>{{ __('Cek lagi syarat IPK di situs resminya') }}</span></li>
                                </ul>

                                <div class="mt-5 flex items-center gap-2 text-sm font-semibold text-brand-teal-deep">
                                    {{ __('Situs resmi') }}
                                    <x-icons.app-icon name="arrow-top-right-on-square" class="size-4" />
                                </div>
                            </div>
                            <figcaption class="mt-3 text-xs text-zinc-700">{{ __('Ilustrasi tampilan, bukan data pengguna.') }}</figcaption>
                        </figure>
                    </div>

                    {{-- Feature 2: Step-by-step Daftar (mirrored layout) --}}
                    <div class="clay mt-8 grid gap-10 rounded-[2.5rem] bg-brand-amber-soft p-6 sm:p-10 lg:grid-cols-2 lg:items-center lg:gap-12" data-reveal>
                        <div class="lg:order-2">
                            <span class="clay-sm flex size-14 items-center justify-center rounded-2xl bg-white text-xl font-extrabold text-brand-ink">02</span>
                            <h3 class="mt-5 text-2xl font-extrabold tracking-tight sm:text-3xl">{{ __('Step-by-step Daftar') }}</h3>
                            <p class="mt-4 leading-relaxed text-zinc-800">
                                {{ __('Setelah memilih beasiswa, kamu mendapat roadmap modul. Setiap modul punya urutan yang sama, jadi kamu selalu tahu sedang di mana dan apa yang perlu dikerjakan berikutnya.') }}
                            </p>
                            <ul class="mt-6 flex flex-col gap-3 text-sm font-medium">
                                @foreach ([
                                    'Materi singkat, lalu Quick Question untuk mengecek pemahamanmu',
                                    'Praktik menulis dengan feedback AI dan AI Mentor untuk bertanya',
                                    'Beasiswa Saya untuk mencatat status sampai hasil pengumuman',
                                ] as $point)
                                    <li class="flex items-start gap-3">
                                        <span class="clay-sm flex size-7 shrink-0 items-center justify-center rounded-lg bg-white">
                                            <x-icons.app-icon name="check" class="size-4 text-brand-ink" />
                                        </span>
                                        <span class="pt-1">{{ __($point) }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        <figure class="lg:order-1">
                            <div class="clay rounded-[2rem] bg-white p-5 sm:p-6">
                                <p class="text-xs font-semibold text-zinc-700">{{ __('Langkah di setiap modul') }}</p>
                                <ol class="mt-3 flex flex-col gap-2">
                                    @foreach ($moduleSteps as $step)
                                        <li @class([
                                            'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium',
                                            'bg-brand-teal-soft' => $step['state'] === 'done',
                                            'clay-sm bg-brand-amber font-bold' => $step['state'] === 'current',
                                            'bg-zinc-100 text-zinc-700' => $step['state'] === 'locked',
                                        ])>
                                            @if ($step['state'] === 'done')
                                                <x-icons.app-icon name="check-circle" class="size-5 text-brand-teal-deep" />
                                            @elseif ($step['state'] === 'current')
                                                <x-icons.app-icon name="pencil-square" class="size-5 text-brand-ink" />
                                            @else
                                                <x-icons.app-icon name="lock-closed" class="size-5 text-zinc-600" />
                                            @endif
                                            {{ __($step['label']) }}
                                        </li>
                                    @endforeach
                                </ol>

                                <div class="mt-4 rounded-2xl bg-brand-mint-soft px-4 py-3">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="text-xs font-semibold text-zinc-700">{{ __('Feedback latihan') }}</p>
                                        <span class="rounded-full bg-white px-2.5 py-0.5 text-xs font-bold text-emerald-800">{{ __('Baik') }}</span>
                                    </div>
                                    <p class="mt-1.5 text-sm">{{ __(\App\Enums\AiScoreLabel::Baik->hint()) }}</p>
                                </div>
                            </div>
                            <figcaption class="mt-3 text-xs text-zinc-700">{{ __('Ilustrasi tampilan, bukan data pengguna.') }}</figcaption>
                        </figure>
                    </div>
                </div>
            </section>

            {{-- How it works --}}
            <section id="cara-kerja" class="scroll-mt-28 bg-warm-sand py-20 sm:py-28" aria-labelledby="cara-kerja-heading">
                <div class="mx-auto w-full max-w-6xl px-4 sm:px-6">
                    <div class="max-w-2xl" data-reveal>
                        <p class="clay-sm inline-flex rounded-full bg-brand-peach-soft px-4 py-1.5 text-sm font-semibold">{{ __('Cara kerja') }}</p>
                        <h2 id="cara-kerja-heading" class="mt-4 text-3xl leading-tight font-extrabold tracking-tight sm:text-5xl">
                            {{ __('Empat tahap, dari profil sampai pengumuman.') }}
                        </h2>
                        <p class="mt-4 text-base leading-relaxed text-zinc-700 sm:text-lg">
                            {{ __('Urutannya sama dengan yang kamu jalani di dalam aplikasi.') }}
                        </p>
                    </div>

                    <ol class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ($journey as $index => $step)
                            <li class="clay {{ $step['tint'] }} flex flex-col rounded-[2rem] p-6" data-reveal style="--sk-reveal-delay: {{ $index * 80 }}ms">
                                <div class="flex items-center justify-between">
                                    <span class="clay-sm flex size-12 items-center justify-center rounded-2xl bg-white text-lg font-extrabold tabular-nums">
                                        {{ $index + 1 }}
                                    </span>
                                    <x-icons.app-icon :name="$step['icon']" class="size-7 text-brand-ink/70" />
                                </div>
                                <h3 class="mt-5 text-lg font-bold">{{ __($step['title']) }}</h3>
                                <p class="mt-2 text-sm leading-relaxed text-zinc-800">{{ __($step['text']) }}</p>
                            </li>
                        @endforeach
                    </ol>

                    {{-- Labels instead of numbers: mentees never see a numeric score. --}}
                    <div class="clay mt-8 grid gap-8 rounded-[2.5rem] bg-white p-6 sm:p-10 lg:grid-cols-12" data-reveal>
                        <div class="lg:col-span-4">
                            <h3 class="text-2xl font-extrabold tracking-tight">{{ __('Label, bukan angka') }}</h3>
                            <p class="mt-3 text-sm leading-relaxed text-zinc-700">
                                {{ __('Setiap jawaban praktik dinilai AI berdasarkan rubrik modul. Kamu melihat satu label beserta saran perbaikan, supaya fokusmu ada di langkah berikutnya, bukan di angka.') }}
                            </p>
                        </div>
                        <dl class="grid gap-4 sm:grid-cols-2 lg:col-span-8">
                            @foreach ($scoreLabels as $item)
                                <div class="clay-sm {{ $item['tint'] }} rounded-2xl p-4">
                                    <dt class="font-bold">{{ __($item['label']) }}</dt>
                                    <dd class="mt-1 text-sm text-zinc-800">{{ __($item['hint']) }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                </div>
            </section>

            {{-- Profiling Kilat: login-free readiness mini assessment --}}
            <livewire:profiling.quick-assessment />

            {{-- Principles: what mentees and their parents can hold us to. --}}
            <section id="persiapan" class="scroll-mt-28 py-20 sm:py-28" aria-labelledby="persiapan-heading">
                <div class="mx-auto grid w-full max-w-6xl gap-12 px-4 sm:px-6 lg:grid-cols-12">
                    <div class="lg:col-span-4" data-reveal>
                        <p class="clay-sm inline-flex rounded-full bg-brand-mint-soft px-4 py-1.5 text-sm font-semibold">{{ __('Prinsip kami') }}</p>
                        <h2 id="persiapan-heading" class="mt-4 text-3xl leading-tight font-extrabold tracking-tight sm:text-5xl">
                            {{ __('Untuk kamu dan orangtuamu.') }}
                        </h2>
                        <p class="mt-4 text-base leading-relaxed text-zinc-700 sm:text-lg">
                            {{ __('Hal yang bisa kamu pegang dari Skular sejak hari pertama.') }}
                        </p>
                    </div>

                    <div class="flex flex-col gap-5 lg:col-span-8">
                        <dl class="grid gap-5 sm:grid-cols-2">
                            @foreach ($principles as $index => $principle)
                                <div @class([
                                    'clay rounded-[2rem] p-6',
                                    $principle['tint'],
                                    'sm:col-span-2' => $loop->last,
                                ]) data-reveal style="--sk-reveal-delay: {{ $index * 60 }}ms">
                                    <dt class="flex items-center gap-3 font-bold">
                                        <span class="clay-sm flex size-10 shrink-0 items-center justify-center rounded-xl bg-white">
                                            <x-icons.app-icon :name="$principle['icon']" class="size-5" />
                                        </span>
                                        {{ __($principle['title']) }}
                                    </dt>
                                    <dd class="mt-3 text-sm leading-relaxed text-zinc-800">{{ __($principle['text']) }}</dd>
                                </div>
                            @endforeach
                        </dl>

                        <aside class="clay flex gap-4 rounded-[2rem] bg-white p-6 text-sm leading-relaxed" data-test="landing-disclaimer">
                            <span class="clay-sm flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-amber">
                                <x-icons.app-icon name="information-circle" class="size-5" />
                            </span>
                            <div>
                                <p class="font-bold">{{ __('Tentang nama beasiswa di Skular') }}</p>
                                <p class="mt-2 text-zinc-800">{{ __($disclaimer) }} {{ __('Nama beasiswa kami tulis secara deskriptif, dan setiap beasiswa punya tautan ke sumber resminya.') }}</p>
                            </div>
                        </aside>
                    </div>
                </div>
            </section>

            {{-- FAQ: only the admin-selected published questions, never the full list. --}}
            <livewire:faq.landing-section />

            {{-- Final CTA: one deep teal clay panel --}}
            <section id="mulai" class="scroll-mt-28 px-4 py-16 sm:px-6 sm:py-24" aria-labelledby="mulai-heading">
                <div class="clay relative mx-auto w-full max-w-6xl overflow-hidden rounded-[2.5rem] bg-brand-teal-deep px-6 py-14 text-white sm:px-12 sm:py-16" data-reveal>
                    <span class="clay pointer-events-none absolute -top-10 -right-10 size-40 rounded-full bg-brand-teal" aria-hidden="true"></span>
                    <span class="clay pointer-events-none absolute -bottom-8 left-1/3 hidden size-24 rounded-full bg-brand-amber/90 sm:block" aria-hidden="true"></span>

                    <div class="relative grid gap-10 lg:grid-cols-12 lg:items-end">
                        <div class="lg:col-span-7">
                            <h2 id="mulai-heading" class="text-3xl leading-tight font-extrabold tracking-tight text-balance sm:text-5xl">
                                {{ __('Mulai dari profilmu hari ini.') }}
                            </h2>
                            <p class="mt-5 max-w-xl leading-relaxed text-brand-teal-soft">
                                {{ __('Isi profiling 8 langkah, lihat beasiswa yang cocok, lalu ikuti langkah daftarnya satu per satu.') }}
                            </p>
                        </div>

                        <div class="flex flex-col items-start gap-4 lg:col-span-5 lg:items-end">
                            <div class="flex flex-wrap items-center gap-3">
                                @auth
                                    <x-landing.cta :href="route('dashboard')" wire:navigate size="lg" iconTrailing="arrow-right">
                                        {{ __('Buka Dashboard') }}
                                    </x-landing.cta>
                                @else
                                    <x-landing.cta :href="route('register')" size="lg" iconTrailing="arrow-right">
                                        {{ __('Mulai Sekarang') }}
                                    </x-landing.cta>

                                    <x-landing.cta :href="route('login')" size="lg" variant="inverse">
                                        {{ __('Masuk') }}
                                    </x-landing.cta>
                                @endauth
                            </div>
                            <p class="text-sm text-brand-teal-soft lg:text-right">
                                {{ __('Hasil seleksi tetap ditentukan penyelenggara beasiswa.') }}
                            </p>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        {{-- Footer --}}
        <footer class="bg-brand-ink text-zinc-300">
            <div class="mx-auto w-full max-w-6xl px-4 sm:px-6">
                <div class="grid gap-10 py-14 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="lg:col-span-2">
                        <x-brand.app-logo href="{{ route('home') }}" class="me-0 min-h-11 w-fit rounded-lg" />

                        <p class="mt-4 max-w-sm text-sm leading-relaxed text-zinc-400">
                            {{ __('Platform persiapan beasiswa untuk pelajar dan mahasiswa Indonesia.') }}
                        </p>
                    </div>

                    <nav aria-label="{{ __('Jelajahi') }}">
                        <p class="text-sm font-semibold text-white">{{ __('Jelajahi') }}</p>

                        <ul class="mt-3 flex flex-col">
                            @foreach ($navLinks as $link)
                                <li>
                                    <a href="{{ $link['href'] }}" class="flex min-h-11 items-center text-sm transition-colors hover:text-brand-amber">
                                        {{ __($link['label']) }}
                                    </a>
                                </li>
                            @endforeach

                            <li>
                                <a href="{{ route('faq.index') }}" class="flex min-h-11 items-center text-sm transition-colors hover:text-brand-amber">
                                    {{ __('FAQ') }}
                                </a>
                            </li>
                        </ul>
                    </nav>

                    <nav aria-label="{{ __('Akun') }}">
                        <p class="text-sm font-semibold text-white">{{ __('Akun') }}</p>

                        <ul class="mt-3 flex flex-col">
                            @auth
                                <li>
                                    <a href="{{ route('dashboard') }}" wire:navigate class="flex min-h-11 items-center text-sm transition-colors hover:text-brand-amber">
                                        {{ __('Dashboard') }}
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('profiling') }}" wire:navigate class="flex min-h-11 items-center text-sm transition-colors hover:text-brand-amber">
                                        {{ __('Profiling') }}
                                    </a>
                                </li>
                            @else
                                <li>
                                    <a href="{{ route('register') }}" class="flex min-h-11 items-center text-sm transition-colors hover:text-brand-amber">
                                        {{ __('Daftar akun') }}
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('login') }}" class="flex min-h-11 items-center text-sm transition-colors hover:text-brand-amber">
                                        {{ __('Masuk') }}
                                    </a>
                                </li>
                            @endauth
                        </ul>
                    </nav>
                </div>

                <div class="flex flex-col gap-3 border-t border-white/10 py-6 text-xs leading-relaxed text-zinc-400">
                    <p class="max-w-3xl">{{ __($disclaimer) }}</p>
                    <p>&copy; {{ now()->year }} {{ config('app.name', 'Skular') }}. {{ __('Seluruh hak cipta dilindungi.') }}</p>
                </div>
            </div>
        </footer>

        @include('partials.landing-tour')
    </body>
</html>
