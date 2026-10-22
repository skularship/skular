@props([
    'variant' => 'quiz',
])

@php
    $is_quiz = $variant === 'quiz';

    $title = $is_quiz ? __('Refleksi Menuju Kuis') : __('Ayo Mulai Praktik');

    $subtitle = $is_quiz
        ? __('Baca dulu arahan ini sebelum menguji pemahamanmu.')
        : __('Konteks dari modul ini sebelum kamu mengerjakan latihan.');

    $section_icon = $is_quiz ? 'clipboard-document-check' : 'beaker';
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

        <p class="text-sm leading-relaxed whitespace-pre-line text-zinc-700 dark:text-zinc-200">
            {{ $module->introduction }}
        </p>

        <p class="flex items-center gap-1.5 text-xs text-zinc-500 dark:text-zinc-400">
            <x-icons.app-icon name="arrow-right" class="size-3.5 text-brand-orange" />
            {{ $subtitle }}
        </p>
    </div>
</section>
