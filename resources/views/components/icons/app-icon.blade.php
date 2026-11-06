@props([
    'name',
    'variant' => 'o',
])

{{--
    Heroicons are rendered straight through the blade-icons svg() helper,
    exactly what blade-icons' own <x-heroicon-*> component calls internally,
    so the markup is identical. Skipping the extra <x-dynamic-component> and
    component layers makes an icon ~80x cheaper; pages render 50-130 icons,
    and that component overhead dominated page render time.
--}}
@if (in_array($name, ['book-open-text', 'chevrons-up-down', 'folder-git-2', 'layout-grid', 'spinner'], true))
    <x-dynamic-component :component="'icons.'.$name" {{ $attributes->class(['shrink-0']) }} />
@else
    @php
        $icon_attributes = $attributes->class(['shrink-0'])->getAttributes();
        $icon_class = $icon_attributes['class'] ?? '';
        unset($icon_attributes['class']);
    @endphp
    {{ svg('heroicon-'.$variant.'-'.$name, $icon_class, $icon_attributes) }}
@endif
