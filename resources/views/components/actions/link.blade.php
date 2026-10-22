@props([
    'as' => 'a',
])

@php
    $classes = 'font-medium underline underline-offset-[6px] hover:decoration-current text-[var(--color-accent-content)] decoration-[color-mix(in_oklab,var(--color-accent-content),transparent_80%)]';
@endphp

@if ($as === 'button')
    <button {{ $attributes->class($classes)->merge(['type' => 'button']) }}>{{ $slot }}</button>
@else
    <a {{ $attributes->class($classes) }}>{{ $slot }}</a>
@endif
