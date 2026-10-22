@props([
    'icon' => 'sparkles',
    'title',
    'description' => null,
])

<div {{ $attributes->class([
    'relative flex flex-col items-center justify-center overflow-hidden rounded-3xl border border-dashed border-brand-teal/25 bg-warm-cream/70 px-6 py-12 text-center dark:border-white/15 dark:bg-zinc-800/40',
]) }}>
    <div class="hero-mesh pointer-events-none absolute inset-x-0 -top-24 h-48 opacity-40" aria-hidden="true"></div>

    <span class="relative mb-4 flex size-14 items-center justify-center rounded-2xl brand-gradient text-brand-ink shadow-pop">
        <x-icons.app-icon :name="$icon" class="size-7" />
    </span>

    <p class="relative text-base font-bold text-zinc-800 dark:text-zinc-100">{{ $title }}</p>

    @if (filled($description))
        <p class="relative mt-1 max-w-md text-sm text-zinc-500 dark:text-zinc-400">{{ $description }}</p>
    @endif

    @if ($slot->isNotEmpty())
        <div class="relative mt-5">{{ $slot }}</div>
    @endif
</div>
