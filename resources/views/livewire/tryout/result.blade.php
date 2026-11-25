{{-- Try Out result (App\Livewire\Tryout\Result). Free for everyone: overall band ring, four subtest cards (band, raw
     score, level meter), Writing/Speaking criteria with short reasons, theory/material cards and the Pro CTA.
     Pending AI steps run one per request (wire:init + poll); failed ones get "Nilai ulang". Bands are numbers here on
     purpose (D5): the Try Out shows estimate scores, unlike the module labels.
     Data: attempt, tryout, result (TryoutResultData), can_theory, can_mentor, premium_enabled. --}}
@php
    $band = fn (?float $value): string => $value === null ? '–' : number_format($value, 1, ',', '');
    $overall = $result->overall_band;
    $ring = $overall === null ? 0 : (int) round($overall / 9 * 100);
    $writing = collect($result->sections)->firstWhere('skill', 'writing');
    $speaking = collect($result->sections)->firstWhere('skill', 'speaking');
@endphp

<div
    class="mx-auto flex w-full max-w-5xl flex-col gap-6"
    @if ($result->needs_assessment) wire:init="advanceAssessment" wire:poll.6s="advanceAssessment" @endif
    data-test="tryout-result"
>
    <a href="{{ route('tryout.index') }}" wire:navigate class="inline-flex w-fit items-center gap-1.5 text-sm font-semibold text-zinc-500 hover:text-brand-teal">
        <x-icons.app-icon name="arrow-left" class="size-4" />
        {{ __('Semua Try Out') }}
    </a>

    {{-- Hero --}}
    <section class="relative overflow-hidden rounded-4xl border border-brand-teal/10 bg-white p-6 shadow-lift sm:p-8">
        <div class="pointer-events-none absolute -end-20 -top-24 size-72 rounded-full bg-brand-teal-soft blur-2xl"></div>
        <div class="relative flex flex-col items-center gap-6 sm:flex-row sm:items-center">
            <div class="relative flex size-40 shrink-0 items-center justify-center" data-test="overall-band">
                <svg viewBox="0 0 120 120" class="size-full -rotate-90">
                    <circle cx="60" cy="60" r="52" fill="none" stroke-width="12" class="stroke-brand-teal-soft" />
                    <circle cx="60" cy="60" r="52" fill="none" stroke-width="12" stroke-linecap="round" class="stroke-brand-teal transition-[stroke-dashoffset] duration-1000"
                        stroke-dasharray="{{ round(2 * M_PI * 52, 2) }}" stroke-dashoffset="{{ round(2 * M_PI * 52 * (1 - $ring / 100), 2) }}" />
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center">
                    @if ($overall === null)
                        <x-icons.app-icon name="spinner" class="size-8 animate-spin text-brand-teal" />
                        <span class="mt-1 text-xs font-semibold text-zinc-500">{{ __('menunggu AI') }}</span>
                    @else
                        <span class="text-5xl font-black text-brand-teal-deep">{{ $band($overall) }}</span>
                        <span class="text-xs font-semibold text-zinc-500">{{ __('band overall') }}</span>
                    @endif
                </div>
            </div>

            <div class="text-center sm:text-start">
                <span class="inline-flex rounded-full bg-brand-amber-soft px-3 py-1 text-xs font-bold text-amber-900">{{ __('Estimasi latihan') }}</span>
                <h1 class="mt-3 text-2xl font-extrabold text-zinc-900 sm:text-3xl">{{ __('Kerja bagus, kamu sudah menyelesaikan semua subtes.') }}</h1>
                <p class="mt-2 text-base text-zinc-600">
                    @if ($result->strongest && $result->weakest && $result->strongest !== $result->weakest)
                        {{ __(':strong jadi kekuatanmu. :weak paling perlu dilatih.', ['strong' => $result->strongest, 'weak' => $result->weakest]) }}
                    @elseif ($result->needs_assessment)
                        {{ __('Writing dan Speaking sedang dinilai AI. Halaman ini diperbarui otomatis, kamu tidak perlu menunggu di sini.') }}
                    @else
                        {{ $tryout->title }}
                    @endif
                </p>
            </div>
        </div>
    </section>

    {{-- Subtest cards --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($result->sections as $section)
            <x-ui.card padding="sm" class="flex flex-col gap-2" data-test="section-card-{{ $section->skill }}">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-bold text-zinc-900">{{ $section->label }}</p>
                    <x-icons.app-icon :name="\App\Enums\TryoutSkill::from($section->skill)->icon()" class="size-5 text-brand-teal" />
                </div>

                @if ($section->band !== null)
                    <p class="text-4xl font-black text-brand-teal-deep">{{ $band($section->band) }}</p>
                @elseif ($section->ai_status === 'failed')
                    <p class="text-sm font-semibold text-red-600">{{ __('Penilaian gagal') }}</p>
                @else
                    <p class="flex items-center gap-2 text-sm font-semibold text-zinc-500">
                        <x-icons.app-icon name="spinner" class="size-4 animate-spin" /> {{ __('Sedang dinilai AI') }}
                    </p>
                @endif

                @if ($section->raw_score !== null)
                    <p class="text-sm text-zinc-600">{{ __(':raw dari :max benar', ['raw' => $section->raw_score, 'max' => $section->max_score]) }}</p>
                    <x-tryout.level-meter :level="$section->final_level" />
                    <p class="text-xs text-zinc-500">{{ __('Level akhir :n dari 5', ['n' => $section->final_level ?? '–']) }}</p>
                @else
                    <p class="text-sm text-zinc-600">
                        {{ collect($section->items)->map(fn ($item) => $item['label'].($item['band'] !== null ? ': '.$band($item['band']) : ''))->implode(' · ') }}
                    </p>
                    @if ($section->skill === 'speaking')
                        <p class="text-xs text-zinc-500">{{ __('Pronunciation estimasi dari transkrip') }}</p>
                    @endif
                @endif

                @if ($section->can_retry)
                    @if ($section->skill === 'writing')
                        @foreach ($section->items as $item)
                            @if ($item['can_retry'])
                                <button type="button" wire:click="retryWriting({{ $item['answer_id'] }})" wire:loading.attr="disabled" class="mt-1 h-10 rounded-xl bg-brand-amber-soft text-sm font-bold text-amber-900 hover:bg-brand-amber/40" data-test="retry-writing">
                                    {{ __('Nilai ulang :item', ['item' => $item['label']]) }}
                                </button>
                            @endif
                        @endforeach
                    @else
                        <button type="button" wire:click="retrySpeaking" wire:loading.attr="disabled" class="mt-1 h-10 rounded-xl bg-brand-amber-soft text-sm font-bold text-amber-900 hover:bg-brand-amber/40" data-test="retry-speaking">
                            {{ __('Nilai ulang') }}
                        </button>
                    @endif
                @endif
            </x-ui.card>
        @endforeach
    </div>

    {{-- Criteria --}}
    <div class="grid gap-4 md:grid-cols-2">
        @foreach ([$writing, $speaking] as $productive)
            @if ($productive && $productive->criteria !== [])
                <x-ui.card padding="md">
                    <h2 class="text-base font-extrabold text-zinc-900">{{ __(':skill per kriteria', ['skill' => $productive->label]) }}</h2>
                    <ul class="mt-4 flex flex-col gap-4">
                        @foreach ($productive->criteria as $criterion)
                            <li x-data="{ open: false }">
                                <button type="button" x-on:click="open = ! open" class="w-full text-start">
                                    <span class="flex items-center justify-between gap-2 text-sm">
                                        <span class="text-zinc-700">{{ $criterion->label }}</span>
                                        <span class="font-extrabold text-zinc-900">{{ $band($criterion->band) }}</span>
                                    </span>
                                    <span class="mt-1.5 block h-2 overflow-hidden rounded-full bg-zinc-100">
                                        <span class="block h-full rounded-full bg-brand-teal-light" style="width: {{ (int) round($criterion->band / 9 * 100) }}%"></span>
                                    </span>
                                </button>
                                @if (filled($criterion->reason))
                                    <p x-show="open" x-collapse class="mt-2 text-sm text-zinc-600">{{ $criterion->reason }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                    @if (filled($productive->summary))
                        <p class="mt-4 rounded-2xl bg-brand-cream px-4 py-3 text-sm text-zinc-700">{{ $productive->summary }}</p>
                    @endif
                </x-ui.card>
            @endif
        @endforeach
    </div>

    {{-- Learn more: theory, PDF, materials, review --}}
    <section class="rounded-4xl bg-brand-teal-deep p-6 text-white shadow-lift sm:p-8">
        <h2 class="text-xl font-extrabold">{{ __('Mau tahu kenapa jawabanmu benar atau salah?') }}</h2>
        <p class="mt-1 text-sm text-white/80">{{ __('Jika kamu ingin mengetahui teori IELTS, jawaban benar beserta pembahasannya, silakan tanya mentor AI kami.') }}</p>

        <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @php
                $cards = [
                    ['icon' => 'chat-bubble-left-right', 'title' => __('Pembahasan + mentor AI'), 'href' => route('tryout.review', $attempt), 'locked' => ! $result->can_review, 'test' => 'cta-review'],
                    ['icon' => 'book-open', 'title' => __('Pelajari teori'), 'href' => route('tryout.theory', $tryout), 'locked' => ! $can_theory, 'test' => 'cta-theory', 'hidden' => ! $tryout->hasTheoryModule()],
                    ['icon' => 'document-text', 'title' => __('Baca PDF teori'), 'href' => route('tryout.theory', $tryout).'#pdf-teori', 'locked' => ! $can_theory, 'test' => 'cta-pdf', 'hidden' => blank($tryout->theory_pdf_path)],
                ];
            @endphp

            @foreach ($cards as $card)
                @continue($card['hidden'] ?? false)
                <a
                    href="{{ $card['locked'] && $premium_enabled ? route('premium') : $card['href'] }}"
                    @if (! $card['locked']) wire:navigate @endif
                    class="flex flex-col gap-2 rounded-3xl bg-white/10 p-4 ring-1 ring-white/15 transition hover:bg-white/15"
                    data-test="{{ $card['test'] }}"
                >
                    <span class="flex items-center justify-between">
                        <x-icons.app-icon :name="$card['icon']" class="size-6 text-brand-amber" />
                        @if ($card['locked'])
                            <span class="inline-flex items-center gap-1 rounded-full bg-brand-orange px-2 py-0.5 text-[0.65rem] font-extrabold text-white">
                                <x-icons.app-icon name="lock-closed" class="size-3" /> PRO
                            </span>
                        @endif
                    </span>
                    <span class="font-bold">{{ $card['title'] }}</span>
                </a>
            @endforeach

            @if ($tryout->hasMaterialsLink())
                <button type="button" wire:click="openMaterials" class="flex flex-col gap-2 rounded-3xl bg-brand-amber p-4 text-start text-brand-ink transition hover:brightness-105" data-test="cta-materials">
                    <x-icons.app-icon name="shopping-bag" class="size-6" />
                    <span class="font-bold">{{ $tryout->materialsLabel() }}</span>
                </button>
            @endif
        </div>

        @if (! $result->can_review && $premium_enabled)
            <x-actions.link-button :href="route('premium')" variant="primary" size="lg" icon="star" class="mt-5">
                {{ __('Buka dengan paket Pro') }}
            </x-actions.link-button>
        @endif
    </section>

    <p class="text-center text-xs text-zinc-500">{{ config('tryout.disclaimer') }}</p>
</div>
