@props([
    'padding' => 'md',
    'variant' => 'plain',
    'hover' => false,
])

@php
    $paddingClasses = match ($padding) {
        'none' => '',
        'sm' => 'p-4',
        'lg' => 'p-8',
        default => 'p-6',
    };

    // Each variant maps to a functional role instead of pure decoration.
    $variantClasses = match ($variant) {
        'amber' => 'tint-amber border-brand-amber/45 dark:border-brand-amber/25 dark:bg-none dark:bg-zinc-800/60',
        'teal' => 'tint-teal border-brand-teal/25 dark:border-brand-teal-light/25 dark:bg-none dark:bg-zinc-800/60',
        'peach' => 'tint-peach border-brand-orange/30 dark:border-brand-orange/25 dark:bg-none dark:bg-zinc-800/60',
        'mint' => 'bg-brand-mint-soft/70 border-emerald-200/80 dark:border-emerald-500/25 dark:bg-emerald-950/30',
        'brand' => 'card-brand border-brand-teal/15 dark:bg-none dark:border-white/10 dark:bg-zinc-800/60',
        default => 'bg-white border-brand-teal/10 dark:border-white/10 dark:bg-zinc-800/60',
    };
@endphp

<div {{ $attributes->class([
    'rounded-3xl border shadow-soft',
    $variantClasses,
    $paddingClasses,
    'sk-lift' => (bool) $hover,
]) }}>
    {{ $slot }}
</div>
