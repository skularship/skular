@props([
    'name',
    'variant' => 'o',
])

@php
    $customIcons = [
        'book-open-text',
        'chevrons-up-down',
        'folder-git-2',
        'layout-grid',
        'spinner',
    ];

    $component = in_array($name, $customIcons, true)
        ? 'icons.'.$name
        : 'heroicon-'.$variant.'-'.$name;
@endphp

<x-dynamic-component :component="$component" {{ $attributes->class(['shrink-0']) }} />
