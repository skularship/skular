@props([
    'size' => 'base',
])

@php
    $sizeClasses = match ($size) {
        'xl' => 'text-lg',
        'lg' => 'text-base',
        'sm' => 'text-xs',
        default => 'text-sm',
    };
@endphp

<div data-flux-subheading {{ $attributes->class([
    $sizeClasses,
    'text-zinc-500 dark:text-white/70',
]) }}>{{ $slot }}</div>
