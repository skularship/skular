@props([
    'icon' => 'sparkles',
    'title',
    'description' => null,
    'variant' => 'initial',
    'size' => 'md',
    'contained' => true,
    'actionLabel' => null,
    'actionUrl' => null,
    'actionIcon' => null,
    'actionIconTrailing' => null,
    'secondaryActionLabel' => null,
    'secondaryActionUrl' => null,
    'secondaryActionIcon' => null,
])

@php
    // One surface, one icon treatment. Variants only tint the icon chip so a
    // search result, a filtered list and a first-visit page stay distinguishable
    // without looking like three different components.
    $iconTone = match ($variant) {
        'search', 'admin' => 'bg-brand-teal-soft ring-brand-teal/20',
        'filter' => 'bg-brand-peach-soft ring-brand-orange/25',
        'activity' => 'bg-brand-mint-soft ring-emerald-500/15',
        default => 'bg-brand-amber-soft ring-brand-amber/35',
    };

    [$padding, $iconClasses, $iconSize, $titleClasses] = match ($size) {
        'sm' => ['px-4 py-6', 'size-12 rounded-2xl', 'size-5', 'text-sm'],
        'lg' => ['px-6 py-14 sm:py-16', 'size-20 rounded-3xl', 'size-9', 'text-lg sm:text-xl'],
        default => ['px-5 py-10 sm:px-8', 'size-16 rounded-2xl', 'size-7', 'text-base sm:text-lg'],
    };

    // A contained empty state never grows into a full-width slab; it stays a
    // focused block that sits naturally inside the page column.
    $width = $contained ? 'mx-auto w-full max-w-2xl' : 'w-full';

    $hasActions = $slot->isNotEmpty() || filled($actionLabel) || filled($secondaryActionLabel);
@endphp

<div {{ $attributes->class([
    'flex flex-col items-center justify-center text-center',
    $width,
    $contained ? 'rounded-3xl border border-brand-teal/12 bg-white/55' : '',
    $padding,
]) }}>
    <span
        class="inline-flex shrink-0 items-center justify-center text-brand-teal-deep ring-1 {{ $iconClasses }} {{ $iconTone }}"
        aria-hidden="true"
    >
        <x-icons.app-icon :name="$icon" class="{{ $iconSize }}" />
    </span>

    <p class="mt-4 max-w-sm text-balance font-bold text-zinc-900 {{ $titleClasses }}">{{ $title }}</p>

    @if (filled($description))
        <p class="mt-2 max-w-md text-sm leading-relaxed text-zinc-500">{{ $description }}</p>
    @endif

    @if ($hasActions)
        <div class="mt-6 flex flex-col items-stretch justify-center gap-2.5 sm:flex-row sm:flex-wrap sm:items-center">
            @if (filled($actionLabel) && filled($actionUrl))
                {{-- Empty-state actions always point at internal routes. --}}
                <x-actions.link-button :href="$actionUrl" :icon="$actionIcon" :iconTrailing="$actionIconTrailing" wire:navigate>
                    {{ $actionLabel }}
                </x-actions.link-button>
            @endif

            @if (filled($secondaryActionLabel) && filled($secondaryActionUrl))
                <x-actions.link-button :href="$secondaryActionUrl" :icon="$secondaryActionIcon" variant="outline" wire:navigate>
                    {{ $secondaryActionLabel }}
                </x-actions.link-button>
            @endif

            {{ $slot }}
        </div>
    @endif
</div>
