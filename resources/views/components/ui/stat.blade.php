@props([
    'label',
    'value',
    'hint' => null,
    'icon' => null,
    'tone' => 'amber',
])

@php
    // Tone drives the icon chip so a row of stats reads as a colourful set.
    $toneClasses = match ($tone) {
        'teal' => 'bg-brand-teal-soft text-brand-teal dark:bg-brand-teal/25 dark:text-brand-teal-light',
        'peach' => 'bg-brand-peach-soft text-brand-orange dark:bg-brand-orange/20 dark:text-brand-peach',
        'mint' => 'bg-brand-mint-soft text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300',
        'rose' => 'bg-brand-rose-soft text-red-600 dark:bg-red-500/20 dark:text-red-300',
        default => 'bg-brand-amber-soft text-brand-gold dark:bg-brand-amber/20 dark:text-brand-amber',
    };
@endphp

<div {{ $attributes->class([
    'flex items-start gap-3 rounded-3xl border border-brand-teal/10 bg-white p-4 shadow-soft dark:border-white/10 dark:bg-zinc-800/60',
]) }}>
    @if (filled($icon))
        <span class="flex size-11 shrink-0 items-center justify-center rounded-2xl {{ $toneClasses }}">
            <x-icons.app-icon :name="$icon" class="size-5" />
        </span>
    @endif

    <div class="min-w-0">
        <p class="text-xs font-semibold tracking-wide text-zinc-500 uppercase dark:text-zinc-400">{{ $label }}</p>
        <p class="mt-0.5 text-2xl font-bold text-zinc-900 dark:text-white">{{ $value }}</p>

        @if (filled($hint))
            <p class="mt-0.5 text-xs leading-snug text-zinc-500 dark:text-zinc-400">{{ $hint }}</p>
        @endif
    </div>
</div>
