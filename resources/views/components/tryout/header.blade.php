{{-- Sticky teal header of a Try Out subtest (<x-tryout.header>): logo, "TO · skill", progress, server timer and the
     finish button with a confirmation dialog. Props: title, progressLabel, progress (0-100|null), deadline (ISO),
     serverNow (ISO), grace (s), skills (label/state list), finishLabel, finishShortLabel (phones), finishAction (Livewire method),
     confirmText, mobileTitle (short title on phones, e.g. "Reading · 6/40"). --}}
@props([
    'title',
    'progressLabel' => null,
    'progress' => null,
    'deadline' => null,
    'serverNow' => null,
    'grace' => 5,
    'skills' => [],
    'finishLabel' => 'Selesaikan subtes',
    'finishShortLabel' => 'Selesai',
    'mobileTitle' => null,
    'finishAction' => 'finishSection',
    'confirmText' => null,
])

<div class="sticky top-0 z-40" x-data="{ confirming: false }">
    <header class="bg-brand-teal-deep text-white shadow-lift">
        <div class="mx-auto flex max-w-7xl items-center gap-3 px-4 py-3 sm:gap-5 sm:px-6">
            <span class="hidden text-xl font-black tracking-tight sm:block">skular</span>
            <p class="min-w-0 truncate text-base font-bold">
                <span class="sm:hidden">{{ $mobileTitle ?? $title }}</span>
                <span class="hidden sm:inline">{{ $title }}</span>
            </p>

            @if ($progress !== null)
                <div class="hidden flex-1 items-center gap-3 md:flex">
                    @if ($progressLabel)
                        <span class="shrink-0 text-sm text-white/80">{{ $progressLabel }}</span>
                    @endif
                    <div class="h-2 flex-1 overflow-hidden rounded-full bg-white/20">
                        <div class="h-full rounded-full bg-brand-amber transition-[width] duration-500" style="width: {{ max(0, min(100, (int) $progress)) }}%"></div>
                    </div>
                </div>
            @else
                <div class="flex-1"></div>
            @endif

            <div class="ms-auto flex items-center gap-2 sm:gap-3">
                @if ($deadline)
                    <x-tryout.timer :deadline="$deadline" :server-now="$serverNow" :grace="$grace" />
                @endif

                <button
                    type="button"
                    x-on:click="confirming = true"
                    class="h-10 shrink-0 rounded-xl border border-white/40 px-3 text-sm font-bold text-white transition hover:bg-white/10 sm:px-4"
                    data-test="finish-section"
                >
                    <span class="sm:hidden">{{ $finishShortLabel }}</span>
                    <span class="hidden sm:inline">{{ $finishLabel }}</span>
                </button>
            </div>
        </div>

        @if ($progress !== null)
            <div class="h-1 bg-white/15 md:hidden">
                <div class="h-full bg-brand-amber transition-[width] duration-500" style="width: {{ max(0, min(100, (int) $progress)) }}%"></div>
            </div>
        @endif
    </header>

    @if ($skills !== [])
        <nav class="border-b border-brand-teal/10 bg-white/95 backdrop-blur" aria-label="{{ __('Urutan subtes') }}">
            <ol class="mx-auto flex max-w-7xl gap-2 overflow-x-auto px-4 py-2 sm:px-6">
                @foreach ($skills as $index => $skill)
                    <li @class([
                        'flex shrink-0 items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold',
                        'bg-brand-teal text-white' => $skill['state'] === 'current',
                        'bg-brand-mint-soft text-emerald-800' => $skill['state'] === 'done',
                        'bg-zinc-100 text-zinc-500' => $skill['state'] === 'next',
                    ])>
                        @if ($skill['state'] === 'done')
                            <x-icons.app-icon name="check" class="size-3.5" />
                        @else
                            <span>{{ $index + 1 }}</span>
                        @endif
                        {{ $skill['label'] }}
                    </li>
                @endforeach
            </ol>
        </nav>
    @endif

    {{-- Confirmation dialog --}}
    <div x-cloak x-show="confirming" x-transition.opacity class="fixed inset-0 z-50 flex items-end justify-center bg-zinc-950/50 p-4 sm:items-center" role="dialog" aria-modal="true">
        <div x-on:click.outside="confirming = false" class="w-full max-w-md rounded-4xl bg-white p-6 shadow-lift">
            <span class="flex size-12 items-center justify-center rounded-2xl bg-brand-amber-soft text-brand-orange">
                <x-icons.app-icon name="flag" class="size-6" />
            </span>
            <h2 class="mt-4 text-lg font-extrabold text-zinc-900">{{ $finishLabel }}?</h2>
            <p class="mt-2 text-sm text-zinc-600">{{ $confirmText ?? __('Setelah dikirim, subtes ini tidak bisa dibuka lagi.') }}</p>
            <div class="mt-6 flex gap-2">
                <button type="button" x-on:click="confirming = false" class="h-12 flex-1 rounded-2xl bg-zinc-100 font-bold text-zinc-700 hover:bg-zinc-200">{{ __('Kembali') }}</button>
                <button
                    type="button"
                    x-on:click="confirming = false; $wire.{{ $finishAction }}()"
                    class="h-12 flex-1 rounded-2xl bg-brand-teal font-bold text-white hover:bg-brand-teal-deep"
                    data-test="confirm-finish"
                >{{ __('Ya, kirim') }}</button>
            </div>
        </div>
    </div>
</div>
