@props([
    'variant' => 'primary',
    'size' => 'base',
    'icon' => null,
    'iconVariant' => 'm',
    'iconTrailing' => null,
    'type' => 'button',
    'loading' => null,
    'loadingText' => null,
    'loadingTarget' => null,
])

@php
    // Loading copy can come from an explicit `loading-text`, or from a shared
    // key in config/loading.php so every save/send/AI action reads the same.
    if (blank($loadingText) && filled($loading)) {
        // Literal keys such as `ai.mentor` must not go through dot notation.
        $copy = config('loading.copy', [])[$loading] ?? null;

        $loadingText = filled($copy) ? __($copy) : null;
    }

    $wireClick = $attributes->whereStartsWith('wire:click')->first();
    $target = $loadingTarget ?? $wireClick;
    $hasStaticDisabled = $attributes->has('disabled');
    $showsLoading = ($type === 'submit' && ! $hasStaticDisabled) || filled($wireClick);

    // A form submit has no single action to target, so it swaps on any request
    // inside its component (auth and wizard forms only ever run one at a time).
    $swapsLabel = $showsLoading && filled($loadingText) && ($type === 'submit' || filled($target));

    $square = $slot->isEmpty() && ! $iconTrailing;

    $sizeClasses = match ($size) {
        'xs' => $square ? 'size-6 text-xs' : 'h-6 px-2 text-xs',
        'sm' => $square ? 'size-8 text-sm' : 'h-8 px-3 text-sm',
        default => $square ? 'size-10 text-sm' : 'h-10 px-4 text-sm',
    };

    $variantClasses = match ($variant) {
        'primary' => 'border-transparent brand-gradient text-[var(--color-accent-foreground)] shadow-sm hover:brightness-105',
        'filled' => 'border-transparent bg-zinc-800/5 text-zinc-800 hover:bg-zinc-800/10 dark:bg-white/10 dark:text-white dark:hover:bg-white/20',
        'outline' => 'border border-zinc-200 bg-white text-zinc-800 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white dark:hover:bg-zinc-600/75',
        'ghost' => 'border-transparent bg-transparent text-zinc-800 hover:bg-zinc-800/5 dark:text-white dark:hover:bg-white/15',
        'danger' => 'border-transparent bg-red-500 text-white hover:bg-red-600 dark:bg-red-600 dark:hover:bg-red-500',
    };

    $iconSize = $square ? 'size-5' : 'size-4';

    $loadingAttributes = $showsLoading ? ['wire:loading.attr' => 'disabled'] : [];
@endphp

<button
    {{ $attributes->class([
        'relative inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-lg font-medium transition',
        'focus:outline-hidden focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-accent focus-visible:ring-offset-accent-foreground',
        'disabled:pointer-events-none disabled:cursor-default disabled:opacity-50',
        $sizeClasses,
        $variantClasses,
    ])->merge(['type' => $type])->merge($loadingAttributes) }}
>
    @if (filled($icon))
        @if ($swapsLabel)
            <span class="inline-flex shrink-0" wire:loading.remove @if (filled($target)) wire:target="{{ $target }}" @endif>
                <x-icons.app-icon :name="$icon" :variant="$iconVariant" class="{{ $iconSize }}" />
            </span>
        @else
            <x-icons.app-icon :name="$icon" :variant="$iconVariant" class="{{ $iconSize }}" />
        @endif
    @endif

    @if ($swapsLabel)
        <span wire:loading.remove @if (filled($target)) wire:target="{{ $target }}" @endif>{{ $slot }}</span>
        <span wire:loading aria-hidden="true" @if (filled($target)) wire:target="{{ $target }}" @endif>{{ $loadingText }}</span>
    @else
        {{ $slot }}
    @endif

    @if (filled($iconTrailing))
        @if ($swapsLabel)
            <span class="inline-flex shrink-0" wire:loading.remove @if (filled($target)) wire:target="{{ $target }}" @endif>
                <x-icons.app-icon :name="$iconTrailing" :variant="$iconVariant" class="{{ $iconSize }}" />
            </span>
        @else
            <x-icons.app-icon :name="$iconTrailing" :variant="$iconVariant" class="{{ $iconSize }}" />
        @endif
    @endif

    @if ($showsLoading)
        <span
            class="hidden shrink-0"
            wire:loading.class.remove="hidden"
            @if (filled($target)) wire:target="{{ $target }}" @endif
            aria-hidden="true"
        >
            <x-icons.spinner class="{{ $iconSize }}" />
        </span>

        {{-- A single live region per button keeps screen readers informed without
             announcing the visible label swap twice. --}}
        <span
            class="sr-only"
            role="status"
            aria-live="polite"
            wire:loading
            @if (filled($target)) wire:target="{{ $target }}" @endif
        >{{ $loadingText ?? __('Lagi diproses…') }}</span>
    @endif
</button>
