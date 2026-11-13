{{-- Public landing page (route home): hero, the two core features, how it works, Profiling Kilat, principles, FAQ and the final call to action. Adds the js class on <html> that enables the scroll reveal in app.js. --}}
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
        ['name' => 'Beasiswa prestasi mahasiswa aktif', 'match' => 'Cocok', 'status' => 'Dipilih', 'status_class' => 'bg-zinc-100 text-zinc-700'],
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
            'title' => 'Isi profiling',
            'text' => 'Delapan pertanyaan singkat tentang jenjang, usia, IPK, dan rencana studimu. Profil ini bisa kamu ubah lagi nanti.',
        ],
        [
            'title' => 'Pilih beasiswa',
            'text' => 'Skular menampilkan beasiswa yang cocok beserta alasannya. Kamu memilih sampai 5 beasiswa dan satu beasiswa utama.',
        ],
        [
            'title' => 'Ikuti roadmap',
            'text' => 'Kerjakan modul satu per satu: baca materi, jawab Quick Question, lalu tulis jawaban praktikmu sendiri dan baca feedbacknya.',
        ],
        [
            'title' => 'Daftar dan laporkan',
            'text' => 'Daftar di situs resmi penyelenggara, unggah bukti pendaftaranmu di Beasiswa Saya, lalu laporkan hasilnya setelah pengumuman.',
        ],
    ];

    $scoreLabels = collect(\App\Enums\AiScoreLabel::cases())
        ->map(fn (\App\Enums\AiScoreLabel $label): array => [
            'label' => $label->label(),
            'hint' => $label->hint(),
        ])
        ->all();

    $principles = [
        [
            'title' => 'Jawabanmu tetap tulisanmu',
            'text' => 'AI di Skular menjelaskan materi, menilai latihan, dan memberi saran. AI tidak menuliskan jawaban akhir untukmu.',
        ],
        [
            'title' => 'Semua tercatat rapi',
            'text' => 'Jawaban latihan disimpan dulu sebelum dinilai. Progres modul dan status setiap beasiswa ada di satu dashboard, jadi mudah kamu tunjukkan ke orangtua.',
        ],
        [
            'title' => 'Materi terus diperbaiki',
            'text' => 'Di akhir setiap modul ada kolom masukan. Masukan itu kami pakai untuk memperbaiki materi berikutnya.',
        ],
        [
            'title' => 'Langsung ke langkah berikutnya',
            'text' => 'Dashboard selalu menunjukkan satu hal paling penting yang perlu kamu kerjakan sekarang.',
        ],
        [
            'title' => 'Tidak ada janji lolos',
            'text' => 'Hasil seleksi ditentukan penyelenggara beasiswa. Skular membantu persiapanmu, bukan menentukan hasilnya.',
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

        {{-- Navbar --}}
        <header
            x-data="{ scrolled: false, open: false }"
            @scroll.window.throttle.120ms="scrolled = window.scrollY > 16"
            @keydown.escape.window="open = false"
            :class="scrolled || open ? 'border-brand-ink/10 bg-warm-cream/95' : 'border-transparent bg-warm-cream'"
            class="sticky top-0 z-50 border-b bg-warm-cream transition-colors duration-300"
        >
            <div class="mx-auto flex h-16 w-full max-w-6xl items-center justify-between gap-4 px-4 sm:h-18 sm:px-6">
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
                            :class="active === '{{ $target }}' ? 'text-brand-teal-deep' : 'text-zinc-700'"
                            class="group relative flex min-h-11 items-center rounded-lg px-3 text-sm font-medium transition-colors hover:text-brand-teal-deep"
                        >
                            {{ __($link['label']) }}
                            <span
                                :class="active === '{{ $target }}' ? 'scale-x-100' : 'scale-x-0 group-hover:scale-x-100'"
                                class="absolute inset-x-3 bottom-2 h-0.5 origin-left rounded-full bg-brand-orange transition-transform duration-300"
                                aria-hidden="true"
                            ></span>
                        </a>
                    @endforeach
                </nav>

                <div class="flex items-center gap-2 sm:gap-3">
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
                            class="hidden min-h-11 items-center rounded-lg px-2 text-sm font-medium text-zinc-700 transition-colors hover:text-brand-teal-deep sm:flex"
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
                        class="inline-flex size-11 items-center justify-center rounded-xl border border-brand-ink/15 bg-white text-brand-ink transition-colors hover:border-brand-teal md:hidden"
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
                class="max-h-[calc(100dvh-4rem)] overflow-y-auto overscroll-contain border-t border-brand-ink/10 bg-warm-cream md:hidden"
            >
                <nav class="mx-auto flex w-full max-w-6xl flex-col gap-1 px-4 py-4 sm:px-6" aria-label="{{ __('Navigasi seluler') }}">
                    @foreach ($navLinks as $link)
                        <a
                            href="{{ $link['href'] }}"
                            @click="open = false"
                            class="flex min-h-11 items-center rounded-xl px-4 text-base font-medium text-zinc-800 transition-colors hover:bg-brand-amber-soft"
                        >
                            {{ __($link['label']) }}
                        </a>
                    @endforeach

                    <div class="mt-2 flex flex-col gap-2 border-t border-brand-ink/10 pt-3 sm:hidden">
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
            <section class="relative pt-10 pb-16 sm:pt-16 sm:pb-24" aria-labelledby="hero-heading">
                <div class="mx-auto grid w-full max-w-6xl gap-12 px-4 sm:px-6 lg:grid-cols-12 lg:items-end lg:gap-10">
                    <div class="lg:col-span-7">
                        <p class="text-sm font-medium text-brand-teal-deep">
                            {{ __('Teman perjalanan belajar setiap pelajar Indonesia') }}
                        </p>

                        <h1 id="hero-heading" class="mt-5 text-[2.15rem] leading-[1.06] font-bold tracking-tight text-balance sm:text-5xl lg:text-[3.6rem]">
                            {{ __('Dari bingung sampai submit, untuk') }}
                            <span class="sk-marker">{{ __('beberapa beasiswa sekaligus.') }}</span>
                        </h1>

                        <p class="mt-6 max-w-xl text-base leading-relaxed text-zinc-700 sm:text-lg">
                            {{ __('Satu kali persiapan untuk banyak beasiswa. Skular mencarikan beasiswa yang cocok dengan profilmu, lalu memandu langkah daftarnya satu per satu dalam satu aplikasi.') }}
                        </p>

                        <div class="mt-8 flex flex-wrap items-center gap-x-5 gap-y-3">
                            @auth
                                <x-landing.cta :href="route('dashboard')" wire:navigate size="lg" iconTrailing="arrow-right">
                                    {{ __('Buka Dashboard') }}
                                </x-landing.cta>
                            @else
                                <x-landing.cta :href="route('register')" size="lg" iconTrailing="arrow-right">
                                    {{ __('Mulai Sekarang') }}
                                </x-landing.cta>
                            @endauth

                            <a href="#cara-kerja" class="flex min-h-11 items-center rounded-lg text-sm font-semibold text-brand-teal-deep underline decoration-brand-teal/30 underline-offset-4 transition-colors hover:decoration-brand-teal">
                                {{ __('Lihat cara kerjanya') }}
                            </a>
                        </div>

                        <p class="mt-6 max-w-md text-sm leading-relaxed text-zinc-600">
                            {{ __('Daftar akun Skular gratis. Pendaftaran beasiswanya sendiri selalu gratis di situs resmi penyelenggara.') }}
                        </p>
                    </div>

                    {{-- Hero visual: a quiet replica of the "Beasiswa Saya" list, clearly marked as an illustration. --}}
                    <figure class="lg:col-span-5">
                        <div class="rounded-2xl border border-brand-ink/10 bg-white p-4 shadow-soft sm:p-5">
                            <div class="flex items-center justify-between gap-3 border-b border-brand-ink/8 pb-3">
                                <p class="text-sm font-semibold">{{ __('Beasiswa pilihanmu') }}</p>
                                <p class="text-xs text-zinc-600">{{ __('maksimal 5') }}</p>
                            </div>

                            <ul class="divide-y divide-brand-ink/8">
                                @foreach ($heroScholarships as $item)
                                    <li class="flex flex-col gap-2 py-3 sm:flex-row sm:items-center sm:justify-between">
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium">{{ __($item['name']) }}</p>
                                            <p class="mt-0.5 text-xs text-brand-teal-deep">{{ __($item['match']) }}</p>
                                        </div>
                                        <span class="w-fit shrink-0 rounded-md px-2 py-1 text-xs font-medium {{ $item['status_class'] }}">
                                            {{ __($item['status']) }}
                                        </span>
                                    </li>
                                @endforeach
                            </ul>

                            <div class="mt-1 rounded-xl bg-warm-cream px-3 py-3">
                                <p class="text-xs font-medium text-zinc-600">{{ __('Langkah berikutnya') }}</p>
                                <p class="mt-1 text-sm font-medium">{{ __('Tulis draf esai motivasi di modul Praktik') }}</p>
                            </div>
                        </div>

                        <figcaption class="mt-3 text-xs text-zinc-600">
                            {{ __('Ilustrasi tampilan, bukan data pengguna.') }}
                        </figcaption>
                    </figure>
                </div>
            </section>

            {{-- Fact strip: only things that exist in the app. Two identical tracks make the loop seamless. --}}
            <div class="sk-marquee-frame border-y border-brand-ink/10 bg-white py-4">
                <div class="sk-marquee" style="--sk-marquee-duration: 60s">
                    @foreach ([false, true] as $is_duplicate)
                        <ul class="sk-marquee__track flex shrink-0 items-center" @if ($is_duplicate) aria-hidden="true" @endif>
                            @foreach ($factStrip as $fact)
                                <li class="flex shrink-0 items-center gap-6 pe-6 text-sm font-medium whitespace-nowrap text-zinc-700 sm:text-base">
                                    {{ __($fact) }}
                                    <span class="size-1.5 rounded-full bg-brand-orange" aria-hidden="true"></span>
                                </li>
                            @endforeach
                        </ul>
                    @endforeach
                </div>
            </div>

            {{-- The two core features --}}
            <section id="manfaat" class="scroll-mt-24 py-20 sm:py-28" aria-labelledby="manfaat-heading">
                <div class="mx-auto w-full max-w-6xl px-4 sm:px-6">
                    <div class="max-w-2xl" data-reveal>
                        <p class="text-sm font-medium text-brand-teal-deep">{{ __('Dua fitur utama') }}</p>
                        <h2 id="manfaat-heading" class="mt-3 text-3xl leading-tight font-bold tracking-tight sm:text-4xl">
                            {{ __('Satu kali persiapan untuk banyak beasiswa.') }}
                        </h2>
                        <p class="mt-4 text-base leading-relaxed text-zinc-700">
                            {{ __('Banyak beasiswa meminta hal yang mirip: esai, CV, dan wawancara. Di Skular kamu menyiapkannya sekali, lalu menyesuaikannya untuk setiap beasiswa yang kamu pilih.') }}
                        </p>
                    </div>

                    {{-- Feature 1: Informasi Beasiswa --}}
                    <div class="mt-16 grid gap-10 border-t border-brand-ink/10 pt-12 lg:grid-cols-12 lg:gap-12" data-reveal>
                        <div class="lg:col-span-5">
                            <p class="font-mono text-sm text-zinc-600">01</p>
                            <h3 class="mt-2 text-2xl font-bold tracking-tight sm:text-3xl">{{ __('Informasi Beasiswa') }}</h3>
                            <p class="mt-4 leading-relaxed text-zinc-700">
                                {{ __('Isi profiling sekali. Skular mencocokkan profilmu dengan syarat setiap beasiswa yang terbuka, lalu menjelaskan bagian mana yang sudah cocok dan mana yang perlu kamu cek lagi.') }}
                            </p>
                            <ul class="mt-6 flex flex-col gap-3 text-sm text-zinc-800">
                                @foreach ([
                                    'Label kecocokan: Sangat Cocok, Cocok, atau Kurang Cocok',
                                    'Alasan kecocokan ditulis terbuka di setiap beasiswa',
                                    'Tautan ke situs resmi penyelenggara untuk cek ulang syarat',
                                ] as $point)
                                    <li class="flex gap-3">
                                        <x-icons.app-icon name="check" class="mt-0.5 size-4 text-brand-teal" />
                                        <span>{{ __($point) }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        <figure class="lg:col-span-6 lg:col-start-7">
                            <div class="rounded-2xl border border-brand-ink/10 bg-white p-5 sm:p-6">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <p class="font-semibold">{{ __('Beasiswa S1 dalam negeri') }}</p>
                                        <p class="mt-1 text-xs text-zinc-600">{{ __('Jenjang S1 · Dalam negeri') }}</p>
                                    </div>
                                    <x-ui.pill variant="success">{{ __('Sangat Cocok') }}</x-ui.pill>
                                </div>

                                <p class="mt-5 text-xs font-medium text-zinc-600">{{ __('Kenapa cocok') }}</p>
                                <ul class="mt-2 flex flex-col gap-2 text-sm">
                                    <li class="flex gap-2"><x-icons.app-icon name="check-circle" class="mt-0.5 size-4 text-emerald-700" /><span>{{ __('Jenjang pendidikanmu sesuai syarat') }}</span></li>
                                    <li class="flex gap-2"><x-icons.app-icon name="check-circle" class="mt-0.5 size-4 text-emerald-700" /><span>{{ __('Usiamu masih dalam batas pendaftaran') }}</span></li>
                                    <li class="flex gap-2"><x-icons.app-icon name="exclamation-circle" class="mt-0.5 size-4 text-amber-700" /><span>{{ __('Cek lagi syarat IPK di situs resminya') }}</span></li>
                                </ul>

                                <div class="mt-5 flex items-center gap-2 border-t border-brand-ink/8 pt-4 text-sm font-medium text-brand-teal-deep">
                                    {{ __('Situs resmi') }}
                                    <x-icons.app-icon name="arrow-top-right-on-square" class="size-4" />
                                </div>
                            </div>
                            <figcaption class="mt-3 text-xs text-zinc-600">{{ __('Ilustrasi tampilan, bukan data pengguna.') }}</figcaption>
                        </figure>
                    </div>

                    {{-- Feature 2: Step-by-step Daftar (mirrored layout) --}}
                    <div class="mt-16 grid gap-10 border-t border-brand-ink/10 pt-12 lg:grid-cols-12 lg:gap-12" data-reveal>
                        <div class="lg:order-2 lg:col-span-5 lg:col-start-8">
                            <p class="font-mono text-sm text-zinc-600">02</p>
                            <h3 class="mt-2 text-2xl font-bold tracking-tight sm:text-3xl">{{ __('Step-by-step Daftar') }}</h3>
                            <p class="mt-4 leading-relaxed text-zinc-700">
                                {{ __('Setelah memilih beasiswa, kamu mendapat roadmap modul. Setiap modul punya urutan yang sama, jadi kamu selalu tahu sedang di mana dan apa yang perlu dikerjakan berikutnya.') }}
                            </p>
                            <ul class="mt-6 flex flex-col gap-3 text-sm text-zinc-800">
                                @foreach ([
                                    'Materi singkat, lalu Quick Question untuk mengecek pemahamanmu',
                                    'Praktik menulis dengan feedback AI dan AI Mentor untuk bertanya',
                                    'Beasiswa Saya untuk mencatat status sampai hasil pengumuman',
                                ] as $point)
                                    <li class="flex gap-3">
                                        <x-icons.app-icon name="check" class="mt-0.5 size-4 text-brand-teal" />
                                        <span>{{ __($point) }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        <figure class="lg:order-1 lg:col-span-6">
                            <div class="rounded-2xl border border-brand-ink/10 bg-white p-5 sm:p-6">
                                <p class="text-xs font-medium text-zinc-600">{{ __('Langkah di setiap modul') }}</p>
                                <ol class="mt-3 flex flex-col gap-2">
                                    @foreach ($moduleSteps as $step)
                                        <li @class([
                                            'flex items-center gap-3 rounded-lg px-3 py-2 text-sm',
                                            'bg-brand-amber-soft font-semibold' => $step['state'] === 'current',
                                            'text-zinc-600' => $step['state'] === 'locked',
                                        ])>
                                            @if ($step['state'] === 'done')
                                                <x-icons.app-icon name="check-circle" class="size-4 text-brand-teal" />
                                            @elseif ($step['state'] === 'current')
                                                <x-icons.app-icon name="pencil-square" class="size-4 text-brand-ink" />
                                            @else
                                                <x-icons.app-icon name="lock-closed" class="size-4 text-zinc-500" />
                                            @endif
                                            {{ __($step['label']) }}
                                        </li>
                                    @endforeach
                                </ol>

                                <div class="mt-5 border-t border-brand-ink/8 pt-4">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="text-xs font-medium text-zinc-600">{{ __('Feedback latihan') }}</p>
                                        <x-ui.pill variant="success">{{ __('Baik') }}</x-ui.pill>
                                    </div>
                                    <p class="mt-2 text-sm text-zinc-800">{{ __(\App\Enums\AiScoreLabel::Baik->hint()) }}</p>
                                </div>
                            </div>
                            <figcaption class="mt-3 text-xs text-zinc-600">{{ __('Ilustrasi tampilan, bukan data pengguna.') }}</figcaption>
                        </figure>
                    </div>
                </div>
            </section>

            {{-- How it works --}}
            <section id="cara-kerja" class="scroll-mt-24 bg-warm-sand py-20 sm:py-28" aria-labelledby="cara-kerja-heading">
                <div class="mx-auto grid w-full max-w-6xl gap-14 px-4 sm:px-6 lg:grid-cols-12 lg:gap-12">
                    <div class="lg:col-span-4" data-reveal>
                        <p class="text-sm font-medium text-brand-teal-deep">{{ __('Cara kerja') }}</p>
                        <h2 id="cara-kerja-heading" class="mt-3 text-3xl leading-tight font-bold tracking-tight sm:text-4xl">
                            {{ __('Empat tahap, dari profil sampai pengumuman.') }}
                        </h2>
                        <p class="mt-4 leading-relaxed text-zinc-700">
                            {{ __('Urutannya sama dengan yang kamu jalani di dalam aplikasi.') }}
                        </p>
                    </div>

                    <ol class="relative flex flex-col gap-10 lg:col-span-7 lg:col-start-6">
                        <span class="absolute top-2 bottom-2 left-[1.1rem] w-px bg-brand-ink/15" aria-hidden="true"></span>
                        @foreach ($journey as $index => $step)
                            <li class="relative flex gap-5" data-reveal style="--sk-reveal-delay: {{ $index * 80 }}ms">
                                <span class="relative flex size-9 shrink-0 items-center justify-center rounded-full border border-brand-ink/15 bg-warm-cream text-sm font-semibold tabular-nums">
                                    {{ $index + 1 }}
                                </span>
                                <div class="pt-1">
                                    <h3 class="text-lg font-semibold">{{ __($step['title']) }}</h3>
                                    <p class="mt-2 leading-relaxed text-zinc-700">{{ __($step['text']) }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ol>

                    {{-- Labels instead of numbers: mentees never see a numeric score. --}}
                    <div class="rounded-2xl border border-brand-ink/10 bg-white p-6 sm:p-8 lg:col-span-12" data-reveal>
                        <div class="grid gap-8 lg:grid-cols-12">
                            <div class="lg:col-span-4">
                                <h3 class="text-xl font-semibold">{{ __('Label, bukan angka') }}</h3>
                                <p class="mt-3 text-sm leading-relaxed text-zinc-700">
                                    {{ __('Setiap jawaban praktik dinilai AI berdasarkan rubrik modul. Kamu melihat satu label beserta saran perbaikan, supaya fokusmu ada di langkah berikutnya, bukan di angka.') }}
                                </p>
                            </div>
                            <dl class="grid gap-x-8 gap-y-5 sm:grid-cols-2 lg:col-span-8">
                                @foreach ($scoreLabels as $item)
                                    <div class="border-t border-brand-ink/10 pt-3">
                                        <dt class="font-semibold">{{ __($item['label']) }}</dt>
                                        <dd class="mt-1 text-sm text-zinc-700">{{ __($item['hint']) }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Profiling Kilat: login-free readiness mini assessment --}}
            <livewire:profiling.quick-assessment />

            {{-- Principles: what mentees and their parents can hold us to. --}}
            <section id="persiapan" class="scroll-mt-24 py-20 sm:py-28" aria-labelledby="persiapan-heading">
                <div class="mx-auto grid w-full max-w-6xl gap-12 px-4 sm:px-6 lg:grid-cols-12">
                    <div class="lg:col-span-4" data-reveal>
                        <p class="text-sm font-medium text-brand-teal-deep">{{ __('Prinsip kami') }}</p>
                        <h2 id="persiapan-heading" class="mt-3 text-3xl leading-tight font-bold tracking-tight sm:text-4xl">
                            {{ __('Untuk kamu dan orangtuamu.') }}
                        </h2>
                        <p class="mt-4 leading-relaxed text-zinc-700">
                            {{ __('Hal yang bisa kamu pegang dari Skular sejak hari pertama.') }}
                        </p>
                    </div>

                    <dl class="lg:col-span-7 lg:col-start-6">
                        @foreach ($principles as $index => $principle)
                            <div class="grid gap-2 border-t border-brand-ink/10 py-6 sm:grid-cols-[14rem_1fr] sm:gap-8" data-reveal style="--sk-reveal-delay: {{ $index * 60 }}ms">
                                <dt class="font-semibold">{{ __($principle['title']) }}</dt>
                                <dd class="leading-relaxed text-zinc-700">{{ __($principle['text']) }}</dd>
                            </div>
                        @endforeach
                    </dl>

                    <aside class="rounded-2xl border-l-4 border-brand-amber bg-white p-5 text-sm leading-relaxed text-zinc-800 lg:col-span-7 lg:col-start-6" data-test="landing-disclaimer">
                        <p class="font-semibold">{{ __('Tentang nama beasiswa di Skular') }}</p>
                        <p class="mt-2">{{ __($disclaimer) }} {{ __('Nama beasiswa kami tulis secara deskriptif, dan setiap beasiswa punya tautan ke sumber resminya.') }}</p>
                    </aside>
                </div>
            </section>

            {{-- FAQ: only the admin-selected published questions, never the full list. --}}
            <livewire:faq.landing-section />

            {{-- Final CTA --}}
            <section id="mulai" class="scroll-mt-24 bg-brand-ink py-20 text-white sm:py-24" aria-labelledby="mulai-heading">
                <div class="mx-auto grid w-full max-w-6xl gap-10 px-4 sm:px-6 lg:grid-cols-12 lg:items-end" data-reveal>
                    <div class="lg:col-span-7">
                        <h2 id="mulai-heading" class="text-3xl leading-tight font-bold tracking-tight text-balance sm:text-5xl">
                            {{ __('Mulai dari profilmu hari ini.') }}
                        </h2>
                        <p class="mt-5 max-w-xl leading-relaxed text-zinc-300">
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
                        <p class="text-sm text-zinc-400 lg:text-right">
                            {{ __('Hasil seleksi tetap ditentukan penyelenggara beasiswa.') }}
                        </p>
                    </div>
                </div>
            </section>
        </main>

        {{-- Footer --}}
        <footer class="border-t border-white/10 bg-brand-ink text-zinc-300">
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
