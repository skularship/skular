{{-- Public Premium page (App\Livewire\Premium\Index). Order: hero, price card (sticky on desktop, right under the hero on phones),
     what Pro gives, launching banner, Free vs Pro, FAQ, closing call. Pro features not built yet carry a "Segera" badge and are never sold as available. --}}
@use('App\Support\Rupiah')

@php
    $year = $quotes->get('pro_12bln');
    $month = $quotes->get('pro_1bln');
    $current = $quotes->get($selected) ?? $year ?? $month;
    $normal_year = $year ? ($year->is_launching ? $year->strike_total : $year->amount) : null;

    $benefits = [
        ['icon' => 'document-check', 'title' => __('Isi sekali, daftar banyak beasiswa'), 'text' => __('Satu profil dan satu tempat dokumen. Berkas, checklist, tracker, dan pengingat deadline siap untuk tiap beasiswa. Kamu tetap mengirim aplikasi di situs penyelenggara.'), 'soon' => false],
        ['icon' => 'sparkles', 'title' => __('Review esai dan CV oleh AI'), 'text' => __('Masukan konkret dalam hitungan detik. Review AI adalah saran, bukan jaminan lolos.'), 'soon' => true],
        ['icon' => 'chat-bubble-left-right', 'title' => __('Latihan wawancara dengan AI'), 'text' => __('Simulasi pertanyaan wawancara beasiswa, lalu umpan balik untuk jawabanmu.'), 'soon' => true],
        ['icon' => 'play', 'title' => __('Video pembelajaran dan live class'), 'text' => __('Video pendek per langkah, plus live class program beasiswa dan rekamannya. Live class diadakan sesekali, jadwal diumumkan.'), 'soon' => true],
        ['icon' => 'document-text', 'title' => __('Template dokumen siap pakai'), 'text' => __('CV, motivation letter, dan surat lain. Tinggal isi, tidak mulai dari kosong.'), 'soon' => true],
    ];

    $compare = [
        [__('Info semua beasiswa'), __('Penuh'), __('Penuh'), false],
        [__('Profiling, rekomendasi, dan roadmap belajar'), __('Penuh'), __('Penuh'), false],
        [__('Vault dokumen'), __('Terkunci'), __('Sampai :n dokumen', ['n' => config('plans.vault.max_files')]), false],
        [__('Checklist berkas per beasiswa'), __('Terkunci'), __('Semua beasiswa pilihanmu'), false],
        [__('Reminder deadline lewat email (H-7, H-3, H-1)'), __('1 beasiswa'), __('Semua beasiswa pilihanmu'), false],
        [__('Review esai dan CV oleh AI'), __('1 per bulan'), __('5/bln (1 bulan), 10/bln (12 bulan)'), true],
        [__('Latihan wawancara AI'), __('1 sesi pratinjau'), __('3/bln (1 bulan), 6/bln (12 bulan)'), true],
        [__('Video pembelajaran'), __('1 pratinjau'), __('Semua'), true],
        [__('Live class dan rekaman'), __('1 rekaman pratinjau'), __('Semua (sesekali, jadwal diumumkan)'), true],
        [__('Template dokumen'), __('1 contoh'), __('Semua'), true],
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
    class="pb-24 lg:pb-0"
>
    {{-- Hero + price card --}}
    <section class="relative overflow-hidden bg-linear-165 from-brand-amber/30 via-warm-cream to-brand-teal/15 pt-12 pb-10 sm:pt-16">
        <div class="relative mx-auto grid w-full max-w-6xl gap-10 px-4 sm:px-6 lg:grid-cols-12 lg:items-start">
            <div class="lg:col-span-7">
                <x-ui.pill variant="brand" icon="sparkles">{{ __('Paket Pro Skular') }}</x-ui.pill>

                <h1 class="mt-5 text-4xl leading-[1.05] font-extrabold tracking-tight text-zinc-900 sm:text-6xl">
                    {{ __('Daftar banyak beasiswa, sekali isi.') }}
                </h1>

                <p class="mt-4 max-w-xl text-base leading-relaxed text-zinc-600">
                    {{ __('Info semua beasiswa tetap gratis. Pro membuka alat untuk menyiapkan banyak beasiswa sekaligus: kamu isi data satu kali, sisanya Skular yang rapikan.') }}
                </p>

                @if ($has_pro && $pro_until)
                    <p class="mt-5 inline-flex items-center gap-2 rounded-2xl bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700">
                        <x-icons.app-icon name="check" class="size-4" />
                        {{ __('Pro kamu aktif sampai :date. Membeli lagi menyambung dari tanggal itu.', ['date' => $pro_until->translatedFormat('j F Y')]) }}
                    </p>
                @endif
            </div>

            {{-- Price card: sticky on desktop, directly under the hero on phones --}}
            <div class="lg:col-span-5 lg:sticky lg:top-24" x-ref="priceCard">
                <div class="clay rounded-4xl bg-white p-5 sm:p-6" data-test="premium-price-card">
                    <p class="text-sm font-semibold text-zinc-500">{{ __('Paket Pro Skular') }}</p>

                    <div class="mt-3 flex flex-col gap-3" role="radiogroup" aria-label="{{ __('Pilih paket') }}">
                        @foreach ([$year, $month] as $quote)
                            @continue($quote === null)
                            @php($is_year = $quote->months > 1)
                            @php($is_selected = $quote->code === $current?->code)

                            <button
                                type="button"
                                role="radio"
                                aria-checked="{{ $is_selected ? 'true' : 'false' }}"
                                wire:click="select('{{ $quote->code }}')"
                                data-test="plan-{{ $quote->code }}"
                                @class([
                                    'relative w-full rounded-3xl border-2 p-4 text-start transition',
                                    'border-brand-teal bg-brand-teal-soft/60' => $is_selected,
                                    'border-zinc-200 bg-white hover:border-brand-teal/50' => ! $is_selected,
                                ])
                            >
                                @if ($is_year)
                                    <span class="absolute -top-3 start-4 rounded-full bg-brand-ink px-3 py-0.5 text-[0.7rem] font-bold tracking-wide text-white">{{ __('PALING POPULER') }}</span>
                                @endif

                                <span class="flex items-center justify-between gap-3">
                                    <span class="flex items-center gap-2 font-bold text-zinc-900">
                                        <span @class(['flex size-5 items-center justify-center rounded-full border-2', 'border-brand-teal' => $is_selected, 'border-zinc-300' => ! $is_selected])>
                                            @if ($is_selected)
                                                <span class="size-2.5 rounded-full bg-brand-teal"></span>
                                            @endif
                                        </span>
                                        {{ $quote->months }} {{ __('bulan') }}
                                    </span>

                                    @if ($quote->saving_percent)
                                        <span class="rounded-full bg-red-600 px-2.5 py-0.5 text-xs font-bold text-white">{{ __('Hemat :percent%', ['percent' => $quote->saving_percent]) }}</span>
                                    @endif
                                </span>

                                <span class="mt-2 block text-3xl font-extrabold tracking-tight text-zinc-900">
                                    {{ Rupiah::format($quote->monthly) }}<span class="text-sm font-semibold text-zinc-500">/bln</span>
                                </span>

                                @if ($quote->strike_monthly)
                                    <span class="block text-sm text-zinc-400 line-through">{{ Rupiah::format($quote->strike_monthly) }}/bln</span>
                                @endif

                                <span class="mt-1 block text-sm text-zinc-600">
                                    {{ __('Sekali bayar :price untuk :months bulan', ['price' => Rupiah::format($quote->amount), 'months' => $quote->months]) }}
                                </span>

                                @if ($quote->is_launching && $quote->launching_until)
                                    <span class="mt-3 block rounded-2xl border border-dashed border-brand-orange/60 bg-brand-peach-soft/60 px-3 py-2 text-xs text-zinc-700">
                                        <strong class="block text-sm text-zinc-900">{{ __('Amankan semua fitur masa depan sebelum harga normal') }}</strong>
                                        {{ __('Promo berakhir :date', ['date' => $quote->launching_until->translatedFormat('j F Y')]) }}
                                    </span>
                                @endif
                            </button>
                        @endforeach
                    </div>

                    @auth
                        @if ($current?->code === 'pro_12bln' && $current->is_launching)
                            <div class="mt-4" data-test="premium-promo">
                                <label for="promo" class="text-sm font-semibold text-zinc-700">{{ __('Punya kode referral?') }}</label>
                                <input id="promo" type="text" wire:model.live.debounce.500ms="promo" maxlength="20" autocomplete="off" placeholder="{{ __('Contoh: K7M2QPX') }}" class="mt-1 w-full rounded-2xl border border-zinc-200 px-4 py-2.5 uppercase" />
                                @if ($promo_error)
                                    <p class="mt-1 text-sm text-red-600" data-test="promo-error">{{ $promo_error }}</p>
                                @elseif ($promo_discount > 0)
                                    <p class="mt-1 text-sm font-semibold text-brand-teal" data-test="promo-ok">{{ __('Diskon :amount. Total jadi :total.', ['amount' => Rupiah::format($promo_discount), 'total' => Rupiah::format($current->amount - $promo_discount)]) }}</p>
                                @endif
                            </div>
                        @endif
                    @endauth

                    <p class="mt-4 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs font-semibold text-zinc-500">
                        <span>QRIS</span><span>GoPay</span><span>{{ __('Virtual account') }}</span><span>{{ __('Kartu') }}</span>
                        <span class="font-normal">{{ __('Aktif setelah bayar. Tanpa auto-renew.') }}</span>
                    </p>

                    @if ($error)
                        <p class="mt-3 rounded-2xl bg-red-50 px-4 py-2 text-sm text-red-700" role="alert">{{ $error }}</p>
                    @endif

                    <button
                        type="button"
                        wire:click="checkout"
                        wire:loading.attr="disabled"
                        x-on:click="loading = true"
                        x-on:livewire:error.window="loading = false"
                        data-test="premium-checkout"
                        class="clay-sm clay-press mt-4 inline-flex min-h-13 w-full items-center justify-center gap-2 rounded-2xl bg-brand-amber px-6 text-base font-bold text-brand-ink transition-colors hover:bg-brand-gold disabled:opacity-60"
                    >
                        {{ __('Ambil :months bulan', ['months' => $current?->months]) }}
                        <x-icons.app-icon name="arrow-right" class="size-4" />
                    </button>

                    <a
                        href="https://wa.me/{{ config('services.support.whatsapp') }}?text={{ rawurlencode('Halo Skular, saya mau tanya soal paket Pro.') }}"
                        target="_blank"
                        rel="noopener"
                        class="mt-3 inline-flex min-h-11 w-full items-center justify-center rounded-2xl border border-zinc-200 bg-white px-5 text-sm font-bold text-zinc-700 transition hover:border-brand-teal hover:text-brand-teal"
                    >
                        {{ __('Tanya lewat WhatsApp') }}
                    </a>

                    @auth
                        <a href="{{ route('payment.index') }}" wire:navigate class="mx-auto mt-4 flex w-fit items-center gap-2 rounded-full border border-zinc-200 px-4 py-1.5 text-xs font-semibold text-zinc-600 transition hover:border-brand-teal hover:text-brand-teal">
                            <x-icons.app-icon name="clock" class="size-3.5" />
                            {{ __('Riwayat Transaksi') }}
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </section>

    {{-- What Pro gives --}}
    <section class="mx-auto w-full max-w-6xl px-4 py-12 sm:px-6">
        <h2 class="text-2xl font-extrabold tracking-tight text-zinc-900 sm:text-3xl">{{ __('Yang kamu dapat di Pro') }}</h2>

        <div class="clay mt-6 divide-y divide-zinc-100 rounded-4xl bg-white">
            @foreach ($benefits as $benefit)
                <div class="flex gap-4 p-5">
                    <x-ui.icon-badge :icon="$benefit['icon']" tone="teal" />
                    <div>
                        <p class="flex flex-wrap items-center gap-2 font-bold text-zinc-900">
                            {{ $benefit['title'] }}
                            @if ($benefit['soon'])
                                <x-ui.pill variant="peach">{{ __('Segera') }}</x-ui.pill>
                            @endif
                        </p>
                        <p class="mt-1 text-sm leading-relaxed text-zinc-600">{{ $benefit['text'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6 rounded-3xl border border-dashed border-brand-orange/60 bg-brand-peach-soft/60 px-5 py-4">
            <p class="font-bold text-zinc-900">{{ __('Beli sekarang, amankan semua fitur masa depan sebelum harga normal!') }}</p>
            <p class="mt-1 text-sm text-zinc-600">{{ __('Fitur berlabel Segera belum rilis. Semua fitur Pro yang rilis selama paketmu aktif langsung terbuka tanpa biaya tambahan.') }}</p>
        </div>
    </section>

    {{-- Free vs Pro --}}
    <section class="mx-auto w-full max-w-4xl px-4 pb-12 sm:px-6">
        <h2 class="text-2xl font-extrabold tracking-tight text-zinc-900 sm:text-3xl">{{ __('Gratis vs Pro') }}</h2>

        <div class="clay mt-6 overflow-x-auto rounded-4xl bg-white">
            <table class="w-full min-w-[34rem] text-start text-sm">
                <thead>
                    <tr class="border-b border-zinc-100">
                        <th class="px-5 py-4 text-start font-bold text-zinc-900">{{ __('Fitur') }}</th>
                        <th class="px-5 py-4 text-start font-bold text-zinc-900">{{ __('Gratis') }}</th>
                        <th class="bg-brand-teal-soft/50 px-5 py-4 text-start font-bold text-zinc-900">{{ __('Pro') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @foreach ($compare as [$feature, $free, $pro, $soon])
                        <tr>
                            <td class="px-5 py-3 text-zinc-700">
                                {{ $feature }}
                                @if ($soon)
                                    <x-ui.pill variant="peach" class="ms-1">{{ __('Segera') }}</x-ui.pill>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-zinc-600">{{ $free }}</td>
                            <td class="bg-brand-teal-soft/50 px-5 py-3 font-semibold text-zinc-800">{{ $pro }}</td>
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
                <details class="clay-sm group rounded-3xl bg-white px-5 py-4">
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
    <section class="px-4 pb-16 sm:px-6">
        <div class="clay mx-auto flex w-full max-w-4xl flex-col items-start gap-5 rounded-[2.5rem] bg-brand-teal-deep px-6 py-10 text-white sm:px-12">
            <h2 class="text-3xl font-extrabold tracking-tight text-balance sm:text-4xl">{{ __('Musim beasiswa ini, kita daftar bareng.') }}</h2>
            <p class="text-brand-teal-soft">{{ __('Amankan semua fitur masa depan sebelum harga normal.') }}</p>
            <button type="button" wire:click="checkout" class="clay-sm clay-press inline-flex min-h-13 items-center gap-2 rounded-2xl bg-brand-amber px-6 text-base font-bold text-brand-ink transition-colors hover:bg-brand-gold">
                {{ __('Ambil :months bulan', ['months' => $current?->months]) }}
                <x-icons.app-icon name="arrow-right" class="size-4" />
            </button>
        </div>
    </section>

    {{-- Phone: bar that appears once the price card has scrolled away --}}
    <div
        x-cloak
        x-show="stickyBar"
        x-transition
        class="fixed inset-x-0 bottom-0 z-40 border-t border-zinc-200 bg-white/95 px-4 py-3 backdrop-blur lg:hidden"
    >
        <div class="mx-auto flex max-w-xl items-center justify-between gap-3">
            <div>
                <p class="text-xs font-semibold text-zinc-500">{{ $current?->name }}</p>
                <p class="text-lg font-extrabold text-zinc-900">{{ $current ? Rupiah::format($current->monthly) : '' }}<span class="text-xs font-semibold text-zinc-500">/bln</span></p>
            </div>
            <button type="button" wire:click="checkout" class="clay-sm clay-press inline-flex min-h-11 items-center gap-2 rounded-2xl bg-brand-amber px-5 text-sm font-bold text-brand-ink transition-colors hover:bg-brand-gold">
                {{ __('Ambil :months bulan', ['months' => $current?->months]) }}
            </button>
        </div>
    </div>
</div>
