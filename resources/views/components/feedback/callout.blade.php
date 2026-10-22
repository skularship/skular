@props([
    'heading' => null,
    'icon' => null,
    'variant' => 'danger',
])

@php
    $variantClasses = match ($variant) {
        'success' => 'border-green-200 bg-green-50 dark:border-green-500/30 dark:bg-green-950',
        'warning' => 'border-amber-300 bg-amber-50 dark:border-amber-500/30 dark:bg-amber-950',
        default => 'border-red-200 bg-red-50 dark:border-red-500/30 dark:bg-red-950',
    };

    $textClasses = match ($variant) {
        'success' => 'text-green-700 dark:text-green-200',
        'warning' => 'text-amber-800 dark:text-amber-200',
        default => 'text-red-700 dark:text-red-200',
    };

    $iconClasses = match ($variant) {
        'success' => 'text-green-500 dark:text-green-400',
        'warning' => 'text-amber-500 dark:text-amber-400',
        default => 'text-red-500 dark:text-red-400',
    };
@endphp

<div {{ $attributes->class(['flex items-start gap-3 rounded-xl border p-3', $variantClasses]) }}>
    @if (filled($icon))
        <x-icons.app-icon :name="$icon" class="mt-0.5 size-5 shrink-0 {{ $iconClasses }}" />
    @endif

    <div class="flex-1">
        @if (filled($heading))
            <div class="text-sm font-medium {{ $textClasses }}">{{ $heading }}</div>
        @endif

        @if ($slot->isNotEmpty())
            <div class="text-sm {{ $textClasses }}">{{ $slot }}</div>
        @endif
    </div>
</div>
