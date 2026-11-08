{{-- Title + description block at the top of auth screens. Props: title, description. --}}
@props([
    'title',
    'description',
])

<div class="flex w-full flex-col text-center">
    <x-display.heading level="1" size="xl">{{ $title }}</x-display.heading>
    <x-display.subheading>{{ $description }}</x-display.subheading>
</div>
