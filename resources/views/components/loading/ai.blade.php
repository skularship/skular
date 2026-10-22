@props([
    'loading' => 'ai.feedback',
    'target' => null,
    'message' => null,
    'detail' => null,
    'skeleton' => false,
    'skeletonLines' => 2,
    'active' => false,
])

@php
    $copy = $message;

    if (blank($copy) && filled($loading)) {
        // Dotted keys such as `ai.mentor` are literal config keys, so read the
        // array directly instead of letting dot notation drill into it.
        $copy = config('loading.copy', [])[$loading] ?? null;
    }

    $copy = filled($copy) ? __($copy) : __('Lagi diproses…');

    $detailCopy = $detail;

    if (blank($detailCopy) && filled($loading)) {
        $detailCopy = config('loading.detail', [])[$loading] ?? null;
    }

    $detailCopy = filled($detailCopy) ? __($detailCopy) : null;
@endphp

<div
    {{ $attributes->class(['rounded-3xl border border-brand-teal/15 bg-linear-to-br from-brand-amber/12 via-white/60 to-brand-teal/10 p-4 dark:border-white/10 dark:from-brand-amber/10 dark:via-zinc-900/60 dark:to-brand-teal/15']) }}
    @unless ($active)
        {{-- `.block` keeps the card full-width while Livewire toggles it. --}}
        wire:loading.block
        @if (filled($target)) wire:target="{{ $target }}" @endif
    @endunless
    role="status"
    aria-live="polite"
    data-test="ai-loading"
>
    <div class="flex items-start gap-3">
        <span class="relative flex size-10 shrink-0 items-center justify-center rounded-2xl brand-gradient text-brand-ink shadow-pop">
            <x-icons.app-icon name="sparkles" class="size-5" />
            <span class="absolute inset-0 rounded-2xl ring-2 ring-brand-amber/40 motion-safe:animate-pulse" aria-hidden="true"></span>
        </span>

        <div class="min-w-0">
            <p class="flex flex-wrap items-center gap-2 text-sm font-bold text-zinc-900 dark:text-white">
                {{ $copy }}
                <span class="sk-loading-dots" aria-hidden="true">
                    <span class="bg-brand-amber"></span>
                    <span class="bg-brand-orange"></span>
                    <span class="bg-brand-teal"></span>
                </span>
            </p>

            @if (filled($detailCopy))
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $detailCopy }}</p>
            @endif
        </div>
    </div>

    @if ($skeleton)
        <x-ui.skeleton :lines="$skeletonLines" class="mt-3" />
    @endif
</div>
