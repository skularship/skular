@props([
    'eyebrow' => null,
    'eyebrowIcon' => null,
    'title',
    'description' => null,
    'level' => '2',
    'tone' => 'brand',
])

@php
    $eyebrowClasses = match ($tone) {
        'teal' => 'bg-brand-teal-soft text-brand-teal-deep dark:bg-brand-teal/25 dark:text-brand-teal-light',
        'peach' => 'bg-brand-peach-soft text-brand-orange dark:bg-brand-orange/20 dark:text-brand-peach',
        'mint' => 'bg-brand-mint-soft text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300',
        default => 'bg-brand-amber/25 text-brand-teal dark:bg-brand-amber/20 dark:text-brand-amber',
    };

    $tag = match ((string) $level) {
        '1' => 'h1',
        '3' => 'h3',
        default => 'h2',
    };
@endphp

<header {{ $attributes->class(['flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between']) }}>
    <div class="min-w-0">
        @if (filled($eyebrow))
            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {{ $eyebrowClasses }}">
                @if (filled($eyebrowIcon))
                    <x-icons.app-icon :name="$eyebrowIcon" class="size-3.5" />
                @endif
                {{ $eyebrow }}
            </span>
        @endif

        <{{ $tag }} class="mt-3 text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">{{ $title }}</{{ $tag }}>

        @if (filled($description))
            <p class="mt-1 max-w-2xl text-sm text-zinc-500 dark:text-zinc-400">{{ $description }}</p>
        @endif
    </div>

    @if ($slot->isNotEmpty())
        <div class="flex shrink-0 flex-wrap items-center gap-2">{{ $slot }}</div>
    @endif
</header>
