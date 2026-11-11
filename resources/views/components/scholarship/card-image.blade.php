{{--
    Scholarship card image with a branded placeholder. Props: url (public image URL or null), alt, muted (grey out a closed scholarship).
    The stored image is a downscaled WebP (max 512 px), loaded lazily inside a fixed-ratio box so the grid never shifts while loading.
    The placeholder is a Skular illustration, never an organiser logo (AGENTS.md rule 10).
--}}
@props([
    'url' => null,
    'alt' => '',
    'muted' => false,
])

<div {{ $attributes->class([
    'relative h-full w-full overflow-hidden',
    'grayscale' => (bool) $muted,
]) }}>
    @if (filled($url))
        <img
            src="{{ $url }}"
            alt="{{ $alt }}"
            width="512"
            height="512"
            loading="lazy"
            decoding="async"
            class="h-full w-full object-cover"
        >
    @else
        <div class="flex h-full w-full items-center justify-center bg-linear-to-br from-brand-teal-soft via-brand-cream to-brand-amber-soft" data-test="scholarship-image-placeholder" aria-hidden="true">
            <span class="pointer-events-none absolute -top-6 -right-6 size-20 rounded-full bg-brand-amber/30"></span>
            <span class="pointer-events-none absolute -bottom-8 -left-4 size-16 rounded-full bg-brand-teal/15"></span>

            <span class="relative flex size-12 items-center justify-center rounded-2xl bg-white/80 text-brand-teal shadow-soft sm:size-14">
                <x-icons.app-icon name="academic-cap" class="size-6 sm:size-7" />
            </span>
        </div>
    @endif
</div>
