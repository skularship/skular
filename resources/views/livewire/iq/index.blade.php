{{-- Tes IQ start page (App\Livewire\Iq\Index): hero with "Tes IQ Latihan", question count and minutes, the five
     subtests, the disclaimer and "Mulai" (or "Lanjutkan" for the attempt in progress), then the mentee's previous
     attempts. Scores are never shown here. Data: test (IqTest|null), question_count, active (IqAttempt|null),
     history (closed attempts), unlocked (paid access). --}}
<div class="mx-auto flex w-full max-w-3xl flex-col gap-6">
    <section class="relative overflow-hidden rounded-4xl bg-brand-teal-deep px-6 py-8 text-white shadow-lift sm:px-10 sm:py-10" data-tour="iq-hero">
        <div class="pointer-events-none absolute -end-16 -top-16 size-64 rounded-full bg-brand-teal-light/30 blur-2xl"></div>
        <div class="pointer-events-none absolute -bottom-20 end-24 size-56 rounded-full bg-brand-amber/25 blur-2xl"></div>

        <div class="relative">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-xs font-semibold backdrop-blur">
                <x-icons.app-icon name="light-bulb" class="size-3.5" />
                {{ __('Latihan') }}
            </span>
            <h1 class="mt-4 text-3xl font-extrabold tracking-tight sm:text-4xl">{{ __('Tes IQ Latihan') }}</h1>

            @if ($test)
                <p class="mt-3 text-base text-white/85">
                    {{ $test->intro ?: __('Kerjakan :n soal pilihan ganda dalam :minutes menit. Pilih jawaban yang menurutmu paling tepat. Jawabanmu tersimpan setiap kali kamu memilih, dan masih bisa kamu ganti sebelum dikirim.', ['n' => $question_count, 'minutes' => $test->duration_minutes]) }}
                </p>

                <div class="mt-6 grid grid-cols-2 gap-2 sm:max-w-sm">
                    <div class="rounded-2xl bg-white/10 px-4 py-3 ring-1 ring-white/15">
                        <p class="text-2xl font-black">{{ $question_count }}</p>
                        <p class="text-xs text-white/75">{{ __('soal') }}</p>
                    </div>
                    <div class="rounded-2xl bg-white/10 px-4 py-3 ring-1 ring-white/15">
                        <p class="text-2xl font-black">{{ $test->duration_minutes }}</p>
                        <p class="text-xs text-white/75">{{ __('menit') }}</p>
                    </div>
                </div>
            @endif

            <div class="mt-5 flex flex-wrap gap-2">
                @foreach (\App\Enums\IqSubtest::cases() as $subtest)
                    <span class="inline-flex items-center gap-1.5 rounded-2xl bg-white/10 px-3 py-1.5 text-sm font-semibold ring-1 ring-white/15">
                        <x-icons.app-icon :name="$subtest->icon()" class="size-4 text-brand-amber" />
                        {{ $subtest->getLabel() }}
                    </span>
                @endforeach
            </div>
        </div>
    </section>

    <p class="flex items-start gap-3 rounded-3xl bg-brand-amber-soft px-5 py-4 text-sm font-semibold text-amber-900" data-test="iq-disclaimer">
        <x-icons.app-icon name="information-circle" class="mt-0.5 size-5" />
        {{ config('iq.disclaimer') }}
    </p>

    @if (! $test || $question_count === 0)
        <x-ui.empty-state icon="light-bulb" :title="__('Tes IQ sedang disiapkan')" :description="__('Soalnya belum siap. Cek lagi nanti ya.')" data-test="iq-empty" />
    @else
        <x-ui.card padding="md" class="flex flex-col gap-4">
            <h2 class="text-lg font-extrabold text-zinc-900">{{ __('Sebelum mulai') }}</h2>
            <ul class="flex flex-col gap-2 text-sm text-zinc-700">
                <li class="flex gap-2"><x-icons.app-icon name="clock" class="size-5 text-brand-teal" />{{ __('Waktu berjalan dari server sejak kamu menekan Mulai, termasuk saat halaman ditutup.') }}</li>
                <li class="flex gap-2"><x-icons.app-icon name="check-circle" class="size-5 text-brand-teal" />{{ __('Jawaban tersimpan otomatis. Saat waktu habis, jawaban yang sudah tersimpan langsung dikirim.') }}</li>
                <li class="flex gap-2"><x-icons.app-icon name="arrows-right-left" class="size-5 text-brand-teal" />{{ __('Kamu bisa pindah soal lewat tombol nomor, lalu tekan Kirim kalau sudah selesai.') }}</li>
                <li class="flex gap-2"><x-icons.app-icon name="lock-closed" class="size-5 text-brand-teal" />{{ __('Soal bisa dikerjakan gratis. Skor, kunci jawaban, pembahasan, materi, dan mentor AI terbuka setelah kamu membuka akses.') }}</li>
            </ul>

            @if ($active)
                <x-actions.link-button :href="route('iq.runner', $active)" variant="teal" size="lg" icon="play" data-test="iq-resume">
                    {{ __('Lanjutkan tes') }}
                </x-actions.link-button>
            @else
                <x-actions.button wire:click="start" variant="primary" size="lg" icon="play" data-test="iq-start">
                    {{ __('Mulai') }}
                </x-actions.button>
            @endif
        </x-ui.card>
    @endif

    @if ($history->isNotEmpty())
        <section class="flex flex-col gap-3" data-test="iq-history">
            <h2 class="text-base font-extrabold text-zinc-900">{{ __('Percobaanmu') }}</h2>
            @foreach ($history as $past)
                <a href="{{ route('iq.result', $past) }}" wire:navigate wire:key="iq-attempt-{{ $past->id }}" class="flex items-center justify-between gap-3 rounded-3xl border border-brand-teal/10 bg-white px-5 py-4 shadow-soft transition hover:border-brand-teal/30">
                    <span>
                        <span class="block text-sm font-bold text-zinc-900">{{ $past->completed_at?->setTimezone('Asia/Jakarta')->translatedFormat('j M Y, H:i') }}</span>
                        <span class="block text-xs text-zinc-500">{{ __(':n dari :total soal dijawab', ['n' => $past->answered_count ?? 0, 'total' => $past->total_questions]) }} · {{ $past->status->getLabel() }}</span>
                    </span>
                    <span class="flex items-center gap-1 text-sm font-bold text-brand-teal">
                        @unless ($unlocked)
                            <x-icons.app-icon name="lock-closed" class="size-4" />
                        @endunless
                        {{ __('Lihat hasil') }}
                    </span>
                </a>
            @endforeach
        </section>
    @endif

    <livewire:onboarding.product-tour tour="iq" />
</div>
