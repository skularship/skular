@props([
    'variant' => 'primary',
    'size' => 'base',
    'icon' => null,
    'iconTrailing' => null,
])

@php
    $sizeClasses = match ($size) {
        'xs' => 'h-6 px-2 text-xs',
        'sm' => 'h-8 px-3 text-sm',
        'lg' => 'h-12 px-6 text-base',
        default => 'h-10 px-4 text-sm',
    };

    $variantClasses = match ($variant) {
        'primary' => 'brand-gradient text-brand-ink shadow-sm hover:brightness-105',
        'outline' => 'border border-zinc-200 bg-white text-zinc-700 hover:border-brand-teal hover:text-brand-teal dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100',
        'teal' => 'bg-brand-teal text-white hover:bg-brand-teal-light',
        'ghost' => 'text-zinc-600 hover:bg-zinc-800/5 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-white/10',
        default => 'bg-[var(--color-accent)] text-[var(--color-accent-foreground)] hover:brightness-105',
    };
@endphp

<a {{ $attributes->class([
    'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-xl font-medium transition',
    'focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-teal focus-visible:ring-offset-2',
    $sizeClasses,
    $variantClasses,
]) }}>
    @if (filled($icon))
        <x-icons.app-icon :name="$icon" class="size-4" />
    @endif

    {{ $slot }}

    @if (filled($iconTrailing))
        <x-icons.app-icon :name="$iconTrailing" class="size-4" />
    @endif
</a>
