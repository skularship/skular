@props([
    'level' => null,
    'size' => 'base',
])

@php
    $tag = match ((string) $level) {
        '1' => 'h1',
        '2' => 'h2',
        '3' => 'h3',
        '4' => 'h4',
        default => 'div',
    };

    $sizeClasses = match ($size) {
        '2xl' => 'text-4xl',
        'xl' => 'text-2xl',
        'lg' => 'text-base',
        'sm' => 'text-xs',
        default => 'text-sm',
    };
@endphp

<{{ $tag }} {{ $attributes->class([
    'font-medium text-zinc-800 dark:text-white',
    $sizeClasses,
    '[&:has(+[data-flux-subheading])]:mb-2',
]) }}>{{ $slot }}</{{ $tag }}>
