@props([
    'faq',
    'level' => 'h3',
    'showCategory' => false,
])

@php
    $headingTag = in_array($level, ['h2', 'h3', 'h4'], true) ? $level : 'h3';
@endphp

<details {{ $attributes->class([
    'group rounded-2xl border border-brand-teal/12 bg-white/90 shadow-soft transition-colors open:border-brand-teal/30 dark:border-white/10 dark:bg-zinc-900/70 dark:open:border-brand-teal-light/40',
]) }}>
    <summary class="flex cursor-pointer list-none items-start justify-between gap-4 rounded-2xl px-5 py-4 focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-teal focus-visible:ring-offset-2 focus-visible:ring-offset-warm-cream sm:px-6 dark:focus-visible:ring-brand-amber dark:focus-visible:ring-offset-zinc-950 [&::-webkit-details-marker]:hidden">
        <span class="min-w-0">
            @if ($showCategory && $faq->category)
                <span class="mb-2 inline-flex items-center gap-1.5 rounded-full bg-brand-amber/25 px-2.5 py-1 text-[0.6875rem] font-bold tracking-wide text-brand-teal uppercase dark:bg-brand-amber/15 dark:text-brand-amber">
                    {{ $faq->category->name }}
                </span>
            @endif

            <{{ $headingTag }} class="text-sm font-bold leading-relaxed text-zinc-900 sm:text-base dark:text-white">
                {{ $faq->question }}
            </{{ $headingTag }}>
        </span>

        <span class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-full bg-brand-teal/8 text-brand-teal transition-transform duration-200 group-open:rotate-180 dark:bg-white/10 dark:text-brand-amber" aria-hidden="true">
            <x-icons.app-icon name="chevron-down" class="size-4" />
        </span>
    </summary>

    <div class="border-t border-brand-teal/10 px-5 pb-5 pt-4 sm:px-6 dark:border-white/10">
        <div class="faq-answer text-sm leading-relaxed text-zinc-600 dark:text-zinc-300">
            {!! str($faq->answer)->sanitizeHtml() !!}
        </div>
    </div>
</details>
