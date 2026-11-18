{{-- Public Premium page (App\Livewire\Premium\Index). One centred column: hero, plan picker with checkout (the "price card"),
     illustrated Pro features, journey timeline, how-it-works example, Free vs Pro, FAQ, closing call, and a bottom bar
     that appears once the price card has scrolled away. Pro features not built yet carry a "Coming Soon" badge and are never sold as available. --}}
@use('App\Support\Rupiah')

@php
    $year = $quotes->get('pro_12bln');
    $month = $quotes->get('pro_1bln');
    $current = $quotes->get($selected) ?? $year ?? $month;
    $normal_year = $year ? ($year->is_launching ? $year->strike_total : $year->amount) : null;
    $whatsapp_url = 'https://wa.me/'.config('services.support.whatsapp').'?text='.rawurlencode('Halo Skular, saya mau tanya soal paket Pro.');

    $benefits = [
        ['image' => 'isi-sekali', 'title' => __('Isi sekali, daftar banyak'), 'text' => __('Satu profil dan satu tempat dokumen untuk semua beasiswa pilihanmu. Kamu tetap mengirim aplikasi di situs penyelenggara.'), 'soon' => false],
        ['image' => 'tracker', 'title' => __('Tracker dan pengingat deadline'), 'text' => __('Checklist berkas tiap beasiswa, plus pengingat email H-7, H-3, dan H-1.'), 'soon' => false],
        ['image' => 'review-esai', 'title' => __('Review esai dan CV oleh AI'), 'text' => __('Masukan konkret dalam hitungan detik. Review AI adalah saran, bukan jaminan lolos.'), 'soon' => true],
        ['image' => 'wawancara', 'title' => __('Latihan wawancara dengan AI'), 'text' => __('Simulasi pertanyaan wawancara beasiswa, lalu umpan balik untuk jawabanmu.'), 'soon' => true],
        ['image' => 'video-live', 'title' => __('Video dan live class'), 'text' => __('Video pendek per langkah, plus live class dan rekamannya. Live class diadakan sesekali, jadwal diumumkan.'), 'soon' => true],
        ['image' => 'template', 'title' => __('Template dokumen'), 'text' => __('CV, motivation letter, dan surat lain. Tinggal isi, tidak mulai dari kosong.'), 'soon' => true],
    ];

    $journey = [
        ['icon' => 'folder-open', 'tone' => 'bg-brand-teal-soft text-brand-teal', 'when' => __('Hari ini'), 'text' => __('Profil dan berkas penting tersimpan di satu tempat.')],
        ['icon' => 'clipboard-document-check', 'tone' => 'bg-brand-amber-soft text-brand-orange', 'when' => __('Minggu ini'), 'text' => __('Checklist tiap beasiswa jelas, tinggal centang.')],
        ['icon' => 'bell-alert', 'tone' => 'bg-brand-mint-soft text-emerald-700', 'when' => __('Jelang deadline'), 'text' => __('Pengingat masuk ke email. Nggak ada yang kelewat.')],
    ];

    // true = check mark, false = locked, string = shown as is.
    $compare = [
        [__('Info semua beasiswa'), true, true, false],
        [__('Profiling, rekomendasi, dan roadmap belajar'), true, true, false],
        [__('Vault dokumen'), false, __('Sampai :n dokumen', ['n' => config('plans.vault.max_files')]), false],
        [__('Checklist berkas per beasiswa'), false, __('Semua beasiswa pilihanmu'), false],
        [__('Reminder deadline lewat email (H-7, H-3, H-1)'), __('1 beasiswa'), __('Semua beasiswa pilihanmu'), false],
        [__('Review esai dan CV oleh AI'), __('1 per bulan'), __('5/bln (1 bulan), 10/bln (12 bulan)'), true],
        [__('Latihan wawancara AI'), __('1 sesi pratinjau'), __('3/bln (1 bulan), 6/bln (12 bulan)'), true],
        [__('Video pembelajaran'), __('1 pratinjau'), true, true],
        [__('Live class dan rekaman'), __('1 rekaman pratinjau'), __('Semua (sesekali, jadwal diumumkan)'), true],
        [__('Template dokumen'), __('1 contoh'), true, true],
    ];

    $faqs = [
        [__('Worth it nggak sih?'), $year ? __('Hitung per bulan: paket 12 bulan hanya :price per bulan. Satu beasiswa yang lolos bernilai jauh lebih besar dari itu.', ['price' => Rupiah::format($year->monthly)]) : __('Hitung per bulan: paket 12 bulan jauh lebih murah dari paket 1 bulan.')],
        [__('Bedanya Pro sama gratis apa?'), __('Info beasiswa tetap gratis, dan tiap fitur bisa dicoba sedikit. Pro membuka semuanya penuh: isi sekali untuk banyak beasiswa, review AI, latihan wawancara AI, video, live class, dan template.')],
        [__('Paket yang lebih lama, fiturnya lebih banyak?'), __('Fitur sama. Bedanya di lama aktif, harga per bulan, dan kuota AI (review 5 vs 10, wawancara 3 vs 6 per bulan).')],
        [__('Kalau aku nggak sempat nonton video?'), __('Video hanya pendukung: pendek dan dicari saat butuh. Fitur utama bekerja tanpa perlu menonton.')],
        [__('Bayarnya gimana?'), __('Sekali bayar lewat QRIS, GoPay, virtual account, dan metode lain. Tanpa auto-renew. Pro aktif setelah pembayaran terkonfirmasi, biasanya langsung.').($normal_year ? ' '.__('Perpanjangan memakai harga normal :price untuk 12 bulan.', ['price' => Rupiah::format($normal_year)]) : '')],
        [__('Bisa dipakai di HP dan laptop?'), __('Bisa, lewat akun yang sama.')],
        [__('Kalau ada fitur baru, harus bayar lagi?'), __('Tidak. Semua fitur Pro baru yang rilis selama paketmu aktif langsung terbuka. Kuota AI tetap berlaku.')],
    ];
@endphp

<div
    x-data="{
        loading: false,
        stickyBar: false,
        openSnap(detail) {
            const finish = () => { window.location.href = detail.finish_url };
            const open = () => window.snap.pay(detail.token, {
                onSuccess: finish,
                onPending: finish,
                onError: finish,
                onClose: () => { this.loading = false },
            });

            if (window.snap) { open(); return; }

            const script = document.createElement('script');
            script.src = @js($snap['snap_js_url']);
            script.setAttribute('data-client-key', @js($snap['client_key']));
            script.onload = open;
            document.head.appendChild(script);
        },
    }"
    x-on:open-snap.window="openSnap($event.detail)"
    x-init="
        const card = $refs.priceCard;
        if (card && 'IntersectionObserver' in window) {
            new IntersectionObserver(([entry]) => { stickyBar = ! entry.isIntersecting && entry.boundingClientRect.top < 0 }).observe(card);
        }
    "
    class="bg-warm-cream pb-36"
>
    {{-- Hero + plan picker --}}
    <section class="relative overflow-hidden bg-linear-180 from-brand-amber/20 via-warm-cream to-warm-cream pt-10 pb-8 sm:pt-14">
        <div class="mx-auto w-full max-w-2xl px-4 sm:px-6">
            <div class="flex items-center gap-3">
                <span class="clay-sm flex size-12 shrink-0 items-center justify-center rounded-2xl bg-brand-teal text-white">
                    <x-icons.app-icon name="sparkles" class="size-6" />
                </span>
                <p class="relative rounded-2xl rounded-bl-md border border-zinc-200 bg-white px-4 py-2 text-sm font-medium text-zinc-700 shadow-xs">
                    {{ __('Isi datanya sekali, sisanya Skular yang rapikan.') }}
                </p>
            </div>

            <h1 class="mt-5 text-[2rem] leading-[1.08] font-extrabold tracking-tight text-zinc-900 sm:text-5xl">
                {{ __('Daftar banyak beasiswa, sekali isi.') }}
            </h1>
            <svg class="mt-1 h-2.5 w-40 text-brand-teal sm:w-56" viewBox="0 0 200 10" fill="none" preserveAspectRatio="none" aria-hidden="true">
                <path d="M2 7 C 50 2, 120 2, 198 6" stroke="currentColor" stroke-width="4" stroke-linecap="round" />
            </svg>

            <ul class="mt-4 flex flex-wrap gap-x-4 gap-y-2 text-sm text-zinc-600">
                @foreach ([__('Info beasiswa tetap gratis'), __('Sekali bayar'), __('Tanpa auto-renew')] as $point)
                    <li class="flex items-center gap-1.5">
                        <x-icons.app-icon name="check" class="size-4 text-brand-teal" />
                        {{ $point }}
                    </li>
                @endforeach
            </ul>

            @if ($has_pro && $pro_until)
                <p class="mt-5 flex items-start gap-2 rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                    <x-icons.app-icon name="check" class="mt-0.5 size-4" />
                    {{ __('Pro kamu aktif sampai :date. Membeli lagi menyambung dari tanggal itu.', ['date' => $pro_until->translatedFormat('j F Y')]) }}
                </p>
            @endif

            {{-- Price card: plan picker, referral code, checkout and consent --}}
            <div class="mt-7" x-ref="priceCard" data-test="premium-price-card">
                <p class="text-sm font-bold text-zinc-500">{{ __('Paket Pro Skular') }}</p>

                <div class="mt-4 flex flex-col gap-5" role="radiogroup" aria-label="{{ __('Pilih paket') }}">
                    @foreach ([$year, $month] as $quote)
                        @continue($quote === null)
                        @php($is_year = $quote->months > 1)
                        @php($is_selected = $quote->code === $current?->code)
                        @php($has_ticket = $quote->is_launching && $quote->launching_until)

                        <button
                            type="button"
                            role="radio"
                            aria-checked="{{ $is_selected ? 'true' : 'false' }}"
                            wire:click="select('{{ $quote->code }}')"
                            data-test="plan-{{ $quote->code }}"
                            @class([
                                'relative w-full rounded-[1.75rem] border-2 bg-white text-start transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-teal',
                                'border-brand-teal-light ring-4 ring-brand-teal-soft' => $is_selected,
                                'border-zinc-200 hover:border-brand-teal-light/60' => ! $is_selected,
                            ])
                        >
                            @if ($is_year)
                                <span class="absolute -top-3.5 start-5 rounded-full bg-brand-ink px-3 py-1 text-[0.7rem] font-bold tracking-wide text-white">{{ __('PALING POPULER') }}</span>
                            @endif

                            <span class="block p-5 sm:p-6">
                                <span class="flex items-center justify-between gap-3">
                                    <span class="flex items-center gap-3 text-lg font-bold text-zinc-900">
                                        <span @class(['flex size-6 items-center justify-center rounded-full border-2', 'border-brand-ink' => $is_selected, 'border-zinc-300' => ! $is_selected])>
                                            @if ($is_selected)
                                                <span class="size-3 rounded-full bg-brand-ink"></span>
                                            @endif
                                        </span>
                                        {{ $quote->months }} {{ __('bulan') }}
                                    </span>

                                    @if ($quote->saving_percent)
                                        <span class="rounded-full bg-red-600 px-3 py-1 text-xs font-bold text-white">{{ __('Hemat :percent%', ['percent' => $quote->saving_percent]) }}</span>
                                    @endif
                                </span>

                                <span class="mt-3 block ps-9">
                                    <span class="block text-4xl font-extrabold tracking-tight text-zinc-900 sm:text-[2.75rem]">
                                        {{ Rupiah::format($quote->monthly) }}<span class="text-base font-semibold text-zinc-500">/bln</span>
                                    </span>

                                    @if ($quote->strike_monthly)
                                        <span class="block text-sm text-zinc-400 line-through">{{ Rupiah::format($quote->strike_monthly) }}/bln</span>
                                    @endif

                                    <span class="mt-1 block text-sm text-zinc-600">
                                        {{ __('Sekali bayar') }} <strong class="font-bold text-zinc-900">{{ Rupiah::format($quote->amount) }}</strong> {{ __('untuk :months bulan', ['months' => $quote->months]) }}
                                    </span>

                                    @if ($is_year)
                                        <span class="mt-4 flex items-center gap-2 border-t border-dashed border-zinc-200 pt-3 text-sm font-semibold text-brand-teal-deep">
                                            <x-icons.app-icon name="receipt-percent" class="size-4" />
                                            {{ __('Harga per bulan paling hemat') }}
                                        </span>
                                    @endif
                                </span>
                            </span>

                            {{-- Launch promo as a torn ticket stub --}}
                            @if ($has_ticket)
                                <span class="relative block rounded-b-[1.6rem] border-t-2 border-dashed border-brand-orange/50 bg-brand-peach-soft/70 px-6 py-3 text-center">
                                    <span @class(['absolute -top-2.5 -start-3.5 size-5 rounded-full border-2 bg-warm-cream', 'border-brand-teal-light' => $is_selected, 'border-zinc-200' => ! $is_selected]) aria-hidden="true"></span>
                                    <span @class(['absolute -top-2.5 -end-3.5 size-5 rounded-full border-2 bg-warm-cream', 'border-brand-teal-light' => $is_selected, 'border-zinc-200' => ! $is_selected]) aria-hidden="true"></span>
                                    <span class="inline-flex items-start gap-2 text-start">
                                        <x-icons.app-icon name="gift" class="mt-0.5 size-5 text-brand-orange" />
                                        <span>
                                            <strong class="block text-sm text-zinc-900">{{ __('Amankan semua fitur masa depan sebelum harga normal') }}</strong>
                                            <span class="block text-xs text-zinc-600">{{ __('Promo berakhir :date', ['date' => $quote->launching_until->translatedFormat('j F Y')]) }}</span>
                                        </span>
                                    </span>
                                </span>
                            @endif
                        </button>
                    @endforeach
                </div>

                @auth
                    @if ($current?->code === 'pro_12bln' && $current->is_launching)
                        <div class="mt-5 rounded-3xl border border-zinc-200 bg-white p-4" data-test="premium-promo">
                            <label for="promo" class="text-sm font-semibold text-zinc-700">{{ __('Punya kode referral?') }}</label>
                            <input id="promo" type="text" wire:model.live.debounce.500ms="promo" maxlength="20" autocomplete="off" placeholder="{{ __('Contoh: K7M2QPX') }}" class="mt-1.5 w-full rounded-2xl border border-zinc-200 bg-warm-cream px-4 py-2.5 uppercase focus:border-brand-teal focus:ring-brand-teal" />
                            @if ($promo_error)
                                <p class="mt-1.5 text-sm text-red-600" data-test="promo-error">{{ $promo_error }}</p>
                            @elseif ($promo_discount > 0)
                                <p class="mt-1.5 text-sm font-semibold text-brand-teal" data-test="promo-ok">{{ __('Diskon :amount. Total jadi :total.', ['amount' => Rupiah::format($promo_discount), 'total' => Rupiah::format($current->amount - $promo_discount)]) }}</p>
                            @endif
                        </div>
                    @endif
                @endauth

                @if ($error)
                    <p class="mt-4 rounded-2xl bg-red-50 px-4 py-2 text-sm text-red-700" role="alert">{{ $error }}</p>
                @endif

                <div class="mt-5 flex items-center gap-3">
                    <a
                        href="{{ $whatsapp_url }}"
                        target="_blank"
                        rel="noopener"
                        aria-label="{{ __('Tanya lewat WhatsApp') }}"
                        title="{{ __('Tanya lewat WhatsApp') }}"
                        class="flex size-13 shrink-0 items-center justify-center rounded-full border border-zinc-200 bg-white text-emerald-600 transition hover:border-emerald-500"
                    >
                        <x-icons.app-icon name="chat-bubble-oval-left-ellipsis" class="size-6" />
                    </a>

                    <button
                        type="button"
                        wire:click="checkout"
                        wire:loading.attr="disabled"
                        x-on:click="loading = true"
                        x-on:livewire:error.window="loading = false"
                        data-test="premium-checkout"
                        class="clay-sm clay-press inline-flex min-h-13 flex-1 items-center justify-center gap-2 rounded-full bg-brand-amber px-6 text-base font-bold text-brand-ink transition-colors hover:bg-brand-gold disabled:opacity-60"
                    >
                        {{ __('Ambil :months bulan', ['months' => $current?->months]) }}
                        <x-icons.app-icon name="arrow-right" class="size-4" />
                    </button>
                </div>

                <x-pricing.payment-methods class="mt-3 justify-center" />

                <p class="mt-2 text-center text-xs leading-relaxed text-zinc-500" data-test="premium-consent">
                    {{ __('Dengan membayar, kamu menyetujui') }}
                    <a href="{{ route('legal.terms') }}" target="_blank" rel="noopener" class="font-semibold underline underline-offset-2">{{ __('Syarat & Ketentuan') }}</a>
                    {{ __('dan') }}
                    <a href="{{ route('legal.refund') }}" target="_blank" rel="noopener" class="font-semibold underline underline-offset-2">{{ __('Kebijakan Pengembalian Dana') }}</a>.
                </p>

                @auth
                    <a href="{{ route('payment.index') }}" wire:navigate class="mx-auto mt-5 flex w-fit items-center gap-2 rounded-full border border-zinc-200 bg-white px-5 py-2.5 text-sm font-semibold text-zinc-700 transition hover:border-brand-teal hover:text-brand-teal">
                        <x-icons.app-icon name="receipt-percent" class="size-4" />
                        {{ __('Riwayat Transaksi') }}
                    </a>
                @endauth
            </div>
        </div>
    </section>

    {{-- What Pro unlocks: illustrated cards, swipeable on phones --}}
    <section class="mx-auto w-full max-w-5xl py-10 sm:px-6 sm:py-14">
        <div class="px-4 sm:px-0">
            <h2 class="text-2xl font-extrabold tracking-tight text-zinc-900 sm:text-3xl">{{ __('Yang kebuka di Pro') }}</h2>
            <p class="mt-1 text-sm text-zinc-600">{{ __('Fitur berlabel Coming Soon belum rilis, dan langsung terbuka saat rilis selama paketmu aktif.') }}</p>
        </div>

        <div class="mt-6 flex snap-x snap-mandatory gap-4 overflow-x-auto scroll-px-4 px-4 pb-3 [scrollbar-width:none] sm:grid sm:grid-cols-2 sm:gap-6 sm:overflow-visible sm:px-0 lg:grid-cols-3">
            @foreach ($benefits as $benefit)
                <article class="w-[78%] shrink-0 snap-start sm:w-auto">
                    <img
                        src="{{ asset('images/pro/'.$benefit['image'].'.webp') }}"
                        alt=""
                        width="600"
                        height="380"
                        loading="lazy"
                        decoding="async"
                        class="aspect-[600/380] w-full rounded-3xl object-cover shadow-[0_18px_30px_-20px_rgb(27_27_24/0.45)]"
                    />
                    <h3 class="mt-3 flex flex-wrap items-center gap-2 text-lg font-bold text-zinc-900">
                        {{ $benefit['title'] }}
                        @if ($benefit['soon'])
                            <x-ui.pill variant="peach">{{ __('Coming Soon') }}</x-ui.pill>
                        @endif
                    </h3>
                    <p class="mt-1 text-sm leading-relaxed text-zinc-600">{{ $benefit['text'] }}</p>
                </article>
            @endforeach
        </div>
    </section>

    {{-- Journey with Pro --}}
    <section class="mx-auto w-full max-w-5xl px-4 pb-12 sm:px-6">
        <h2 class="text-2xl font-extrabold tracking-tight text-zinc-900 sm:text-3xl">{{ __('Perjalananmu bareng Pro') }}</h2>

        <ol class="relative mt-8 grid gap-8 md:grid-cols-3 md:gap-6">
            <span class="absolute top-6 bottom-6 start-6 w-0.5 bg-linear-180 from-brand-teal-light/60 to-emerald-300/70 md:top-6 md:start-12 md:end-12 md:bottom-auto md:h-0.5 md:w-auto md:bg-linear-90" aria-hidden="true"></span>

            @foreach ($journey as $step)
                <li class="relative flex gap-4 md:flex-col md:gap-0">
                    <span class="flex size-12 shrink-0 items-center justify-center rounded-full border-4 border-warm-cream {{ $step['tone'] }}">
                        <x-icons.app-icon :name="$step['icon']" class="size-5" />
                    </span>
                    <span class="md:mt-4">
                        <span class="block text-xs font-bold tracking-wider text-zinc-500 uppercase">{{ $step['when'] }}</span>
                        <span class="mt-1 block text-lg leading-snug font-extrabold text-zinc-900 sm:text-xl">{{ $step['text'] }}</span>
                    </span>
                </li>
            @endforeach
        </ol>
    </section>

    {{-- How it works: an illustrative example, not real data --}}
    <section class="mx-auto w-full max-w-5xl px-4 pb-12 sm:px-6">
        <h2 class="text-2xl font-extrabold tracking-tight text-zinc-900 sm:text-3xl">{{ __('Gini cara kerjanya') }}</h2>

        <div class="mt-6 rounded-[2rem] border border-zinc-200 bg-white p-5 sm:p-6">
            <div class="flex items-center justify-between gap-3">
                <h3 class="text-lg font-bold text-zinc-900">{{ __('Satu profil, banyak beasiswa') }}</h3>
                <span class="text-xs font-bold tracking-wider text-zinc-500">{{ __('CONTOH') }}</span>
            </div>

            <div class="mt-4 grid items-center gap-3 rounded-3xl bg-brand-amber-soft/50 p-3 sm:grid-cols-[9rem_1fr] sm:gap-4 sm:p-4">
                <div class="flex items-center gap-3 rounded-2xl border border-zinc-200 bg-white p-4 sm:flex-col sm:items-start">
                    <x-icons.app-icon name="user-circle" class="size-7 text-brand-teal" />
                    <p class="text-sm leading-tight font-bold text-zinc-800">{{ __('Profil dan berkasmu') }}</p>
                </div>

                <div class="flex flex-col gap-2.5">
                    <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-3">
                        <p class="flex items-center gap-2 text-sm text-zinc-600"><x-icons.app-icon name="clipboard-document-check" class="size-4" /> {{ __('Checklist berkas') }}</p>
                        <p class="mt-0.5 text-sm font-bold text-zinc-900">{{ __('Beasiswa S2 luar negeri') }}</p>
                        <span class="mt-2 flex items-center gap-2 text-xs font-semibold text-brand-teal-deep">
                            <span class="h-1.5 flex-1 rounded-full bg-zinc-100"><span class="block h-full w-2/3 rounded-full bg-brand-teal"></span></span>
                            {{ __('4 dari 6 siap') }}
                        </span>
                    </div>
                    <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-3">
                        <p class="flex items-center gap-2 text-sm text-zinc-600"><x-icons.app-icon name="folder-open" class="size-4" /> {{ __('Vault dokumen') }}</p>
                        <p class="mt-0.5 text-sm font-bold text-zinc-900">{{ __('CV, ijazah, transkrip') }}</p>
                        <p class="mt-1 flex items-center gap-1.5 text-xs font-semibold text-emerald-700"><x-icons.app-icon name="check" class="size-3.5" /> {{ __('Dipakai ulang untuk beasiswa lain') }}</p>
                    </div>
                    <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-3">
                        <p class="flex items-center gap-2 text-sm text-zinc-600"><x-icons.app-icon name="bell-alert" class="size-4" /> {{ __('Pengingat') }}</p>
                        <p class="mt-0.5 flex items-center gap-2 text-sm font-bold text-zinc-900">
                            {{ __('Deadline beasiswa S1 dalam negeri') }}
                            <span class="shrink-0 rounded-full bg-brand-peach-soft px-2 py-0.5 text-xs font-bold whitespace-nowrap text-brand-orange">H-7</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Free vs Pro --}}
    <section class="mx-auto w-full max-w-3xl px-4 pb-12 sm:px-6">
        <h2 class="text-2xl font-extrabold tracking-tight text-zinc-900 sm:text-3xl">{{ __('Gratis vs Pro') }}</h2>

        <div class="mt-6 overflow-hidden rounded-[1.75rem] border border-zinc-200 bg-white shadow-[0_18px_30px_-24px_rgb(27_27_24/0.4)]">
            <table class="w-full table-fixed text-sm">
                <thead>
                    <tr class="border-b border-zinc-100">
                        <th class="px-4 py-4 text-start font-bold text-zinc-900 sm:px-5">{{ __('Fitur') }}</th>
                        <th class="w-24 px-2 py-4 text-center font-bold text-zinc-900 sm:w-36">{{ __('Gratis') }}</th>
                        <th class="w-28 bg-brand-teal-soft/50 px-2 py-4 text-center font-bold text-brand-teal-deep sm:w-48">{{ __('Pro') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @foreach ($compare as [$feature, $free, $pro, $soon])
                        <tr>
                            <td class="px-4 py-3.5 text-zinc-800 sm:px-5">
                                {{ $feature }}
                                @if ($soon)
                                    <x-ui.pill variant="peach" class="ms-1">{{ __('Coming Soon') }}</x-ui.pill>
                                @endif
                            </td>
                            @foreach ([$free, $pro] as $index => $value)
                                <td @class(['px-2 py-3.5 text-center text-xs sm:text-sm', 'bg-brand-teal-soft/50 font-semibold text-zinc-800' => $index === 1, 'text-zinc-500' => $index === 0])>
                                    @if ($value === true)
                                        <x-icons.app-icon name="check" class="mx-auto size-5 text-brand-ink" />
                                        <span class="sr-only">{{ __('Ya') }}</span>
                                    @elseif ($value === false)
                                        {{ __('Terkunci') }}
                                    @else
                                        {{ $value }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    {{-- FAQ --}}
    <section class="mx-auto w-full max-w-3xl px-4 pb-12 sm:px-6">
        <h2 class="text-2xl font-extrabold tracking-tight text-zinc-900 sm:text-3xl">{{ __('Pertanyaan soal Pro') }}</h2>

        <div class="mt-6 flex flex-col gap-3">
            @foreach ($faqs as [$question, $answer])
                <details class="group rounded-3xl border border-zinc-200 bg-white px-5 py-4 open:border-brand-teal-light/60">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 font-bold text-zinc-900">
                        {{ $question }}
                        <x-icons.app-icon name="chevron-down" class="size-4 shrink-0 text-zinc-500 transition group-open:rotate-180" />
                    </summary>
                    <p class="mt-3 text-sm leading-relaxed text-zinc-600">{{ $answer }}</p>
                </details>
            @endforeach
        </div>
    </section>

    {{-- Closing call --}}
    <section class="px-4 sm:px-6">
        <div class="clay relative mx-auto flex w-full max-w-3xl flex-col items-start gap-5 overflow-hidden rounded-[2.5rem] bg-brand-teal-deep px-6 py-10 text-white sm:px-12">
            <span class="pointer-events-none absolute -top-10 -end-10 size-40 rounded-full bg-brand-teal" aria-hidden="true"></span>
            <h2 class="relative text-3xl font-extrabold tracking-tight text-balance sm:text-4xl">{{ __('Musim beasiswa ini, kita daftar bareng.') }}</h2>
            <p class="relative text-brand-teal-soft">{{ __('Amankan semua fitur masa depan sebelum harga normal.') }}</p>
            <button type="button" wire:click="checkout" class="clay-sm clay-press relative inline-flex min-h-13 items-center gap-2 rounded-full bg-brand-amber px-6 text-base font-bold text-brand-ink transition-colors hover:bg-brand-gold">
                {{ __('Ambil :months bulan', ['months' => $current?->months]) }}
                <x-icons.app-icon name="arrow-right" class="size-4" />
            </button>
            <p class="relative text-xs text-brand-teal-soft" data-test="premium-consent">
                {{ __('Dengan membayar, kamu menyetujui') }}
                <a href="{{ route('legal.terms') }}" target="_blank" rel="noopener" class="font-semibold underline underline-offset-2">{{ __('Syarat & Ketentuan') }}</a>
                {{ __('dan') }}
                <a href="{{ route('legal.refund') }}" target="_blank" rel="noopener" class="font-semibold underline underline-offset-2">{{ __('Kebijakan Pengembalian Dana') }}</a>.
            </p>
        </div>
    </section>

    {{-- Bottom bar (all widths) once the price card has scrolled away --}}
    <div
        x-cloak
        x-show="stickyBar"
        x-transition.opacity
        class="fixed inset-x-0 bottom-0 z-40 border-t border-zinc-200 bg-white/95 px-4 pt-2 pb-3 backdrop-blur"
    >
        <div class="mx-auto flex max-w-xl flex-col gap-2">
            <x-pricing.payment-methods class="justify-center" />

            <div class="flex items-center gap-3">
                <a
                    href="{{ $whatsapp_url }}"
                    target="_blank"
                    rel="noopener"
                    aria-label="{{ __('Tanya lewat WhatsApp') }}"
                    class="flex size-12 shrink-0 items-center justify-center rounded-full border border-zinc-200 bg-white text-emerald-600 transition hover:border-emerald-500"
                >
                    <x-icons.app-icon name="chat-bubble-oval-left-ellipsis" class="size-6" />
                </a>
                <button type="button" wire:click="checkout" class="clay-sm clay-press inline-flex min-h-12 flex-1 items-center justify-center gap-2 rounded-full bg-brand-amber px-5 text-base font-bold text-brand-ink transition-colors hover:bg-brand-gold">
                    {{ __('Ambil :months bulan', ['months' => $current?->months]) }}
                    <span class="hidden font-semibold text-brand-ink/70 sm:inline">&middot; {{ $current ? Rupiah::format($current->monthly) : '' }}/bln</span>
                    <x-icons.app-icon name="arrow-right" class="size-4" />
                </button>
            </div>

            <p class="text-center text-[0.6875rem] text-zinc-500">
                {{ __('Dengan membayar, kamu menyetujui') }}
                <a href="{{ route('legal.terms') }}" target="_blank" rel="noopener" class="underline">{{ __('S&K') }}</a>
                &amp;
                <a href="{{ route('legal.refund') }}" target="_blank" rel="noopener" class="underline">{{ __('Kebijakan Pengembalian Dana') }}</a>.
            </p>
        </div>
    </div>
</div>
