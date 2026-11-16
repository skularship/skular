{{-- One numbered section of a legal page (<x-legal.section>). Props: id (stable anchor), number, title. Paragraphs, lists and tables go in the slot. --}}
@props([
    'id',
    'number',
    'title',
])

<section id="{{ $id }}" aria-labelledby="{{ $id }}-judul" class="scroll-mt-24 space-y-3">
    <h2 id="{{ $id }}-judul" class="text-xl font-bold text-brand-ink">{{ $number }}. {{ $title }}</h2>
    {{ $slot }}
</section>
