{{-- Kata Pengantar card. Prop: variant (pahami | quiz | practice) changes the title and hint. --}}
@props([
    'variant' => 'quiz',
])

@php
    [$title, $subtitle, $section_icon] = match ($variant) {
        'pahami' => [__('Sebelum Mulai Belajar'), __('Baca pengantar ini dulu, lalu lanjut ke inti materi di bawah.'), 'book-open'],
        'quiz' => [__('Refleksi Menuju Kuis'), __('Baca dulu arahan ini sebelum menguji pemahamanmu.'), 'clipboard-document-check'],
        default => [__('Ayo Mulai Praktik'), __('Konteks dari modul ini sebelum kamu mengerjakan latihan.'), 'beaker'],
    };
@endphp

<section
    class="relative overflow-hidden rounded-2xl border border-brand-orange/30 bg-linear-135 from-brand-amber/20 via-brand-amber/10 to-brand-orange/10 p-5 sm:p-6"
    data-test="module-introduction"
>
    <div class="flex flex-col gap-3">
        <div class="flex items-center gap-2">
            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-brand-orange text-white shadow-sm">
                <x-icons.app-icon :name="$section_icon" class="size-4" />
            </span>

            <div>
                <p class="text-xs font-semibold tracking-wide text-brand-teal uppercase dark:text-brand-teal-light">
                    {{ __('Kata Pengantar') }}
                </p>
                <p class="text-base font-bold text-zinc-900 dark:text-white">{{ $title }}</p>
            </div>
        </div>

        <x-learning.rich-content :content="$module->introduction" :level="4" />

        <p class="flex items-center gap-1.5 text-xs text-zinc-500 dark:text-zinc-400">
            <x-icons.app-icon name="arrow-right" class="size-3.5 text-brand-orange" />
            {{ $subtitle }}
        </p>
    </div>
</section>
