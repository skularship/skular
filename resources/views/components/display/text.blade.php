@props([
    'inline' => false,
    'variant' => null,
    'color' => null,
    'size' => null,
])

@php
    $sizeClasses = match ($size) {
        'xl' => 'text-lg',
        'lg' => 'text-base',
        'sm' => 'text-xs',
        default => 'text-sm',
    };

    $colorClasses = match ($color) {
        'red' => 'text-red-600 dark:text-red-400',
        'green' => 'text-green-600 dark:text-green-500',
        'lime' => 'text-lime-600 dark:text-lime-500',
        'amber' => 'text-amber-600 dark:text-amber-500',
        default => match ($variant) {
            'strong' => 'text-zinc-800 dark:text-white',
            'subtle' => 'text-zinc-400 dark:text-white/50',
            default => 'text-zinc-500 dark:text-white/70',
        },
    };
@endphp

@if ($inline)
    <span {{ $attributes->class(['font-normal', $sizeClasses, $colorClasses]) }}>{{ $slot }}</span>
@else
    <p {{ $attributes->class(['font-normal', $sizeClasses, $colorClasses]) }}>{{ $slot }}</p>
@endif
