@props([
    'variant' => 'neutral',
    'icon' => null,
])

@php
    $variantClasses = match ($variant) {
        'success' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300',
        'warning' => 'bg-amber-50 text-amber-800 dark:bg-amber-500/15 dark:text-amber-200',
        'danger' => 'bg-red-50 text-red-700 dark:bg-red-500/15 dark:text-red-300',
        'info' => 'bg-brand-teal-soft text-brand-teal-deep dark:bg-brand-teal/25 dark:text-brand-teal-light',
        'peach' => 'bg-brand-peach-soft text-brand-orange dark:bg-brand-orange/20 dark:text-brand-peach',
        'brand' => 'bg-brand-amber/25 text-brand-teal dark:text-brand-amber',
        default => 'bg-zinc-100 text-zinc-700 dark:bg-white/10 dark:text-zinc-300',
    };
@endphp

<span {{ $attributes->class([
    'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold',
    $variantClasses,
]) }}>
    @if (filled($icon))
        <x-icons.app-icon :name="$icon" class="size-3.5" />
    @endif

    {{ $slot }}
</span>
