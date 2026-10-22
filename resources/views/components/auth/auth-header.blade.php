@props([
    'title',
    'description',
])

<div class="flex w-full flex-col text-center">
    <x-display.heading level="1" size="xl">{{ $title }}</x-display.heading>
    <x-display.subheading>{{ $description }}</x-display.subheading>
</div>
