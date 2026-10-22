@props([
    'icon',
    'tone' => 'amber',
    'size' => 'md',
    'solid' => false,
])

@php
    $sizeClasses = match ($size) {
        'sm' => 'size-8 rounded-xl',
        'lg' => 'size-14 rounded-2xl',
        default => 'size-11 rounded-2xl',
    };

    $iconSize = match ($size) {
        'sm' => 'size-4',
        'lg' => 'size-7',
        default => 'size-5',
    };

    $toneClasses = match ($tone) {
        'teal' => 'bg-brand-teal-soft text-brand-teal dark:bg-brand-teal/25 dark:text-brand-teal-light',
        'peach' => 'bg-brand-peach-soft text-brand-orange dark:bg-brand-orange/20 dark:text-brand-peach',
        'mint' => 'bg-brand-mint-soft text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300',
        'rose' => 'bg-brand-rose-soft text-red-600 dark:bg-red-500/20 dark:text-red-300',
        default => 'bg-brand-amber-soft text-brand-gold dark:bg-brand-amber/20 dark:text-brand-amber',
    };
@endphp

<span {{ $attributes->class([
    'inline-flex shrink-0 items-center justify-center',
    $sizeClasses,
    $solid ? 'brand-gradient text-brand-ink shadow-pop' : $toneClasses,
]) }}>
    <x-icons.app-icon :name="$icon" class="{{ $iconSize }}" />
</span>
