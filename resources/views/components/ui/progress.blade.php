@props([
    'value' => 0,
    'label' => null,
    'showValue' => true,
    'size' => 'md',
    'tone' => 'brand',
])

@php
    $percentage = max(0, min(100, (int) $value));
    $height = $size === 'sm' ? 'h-1.5' : 'h-2.5';

    $toneClasses = match ($tone) {
        'teal' => 'brand-gradient-teal',
        'mint' => 'bg-emerald-500',
        default => 'brand-gradient',
    };
@endphp

<div {{ $attributes }}>
    @if (filled($label) || $showValue)
        <div class="mb-1.5 flex items-center justify-between text-xs font-medium text-zinc-600 dark:text-zinc-300">
            @if (filled($label))
                <span>{{ $label }}</span>
            @endif

            @if ($showValue)
                <span>{{ $percentage }}%</span>
            @endif
        </div>
    @endif

    <div
        class="{{ $height }} w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-white/10"
        role="progressbar"
        aria-valuenow="{{ $percentage }}"
        aria-valuemin="0"
        aria-valuemax="100"
        aria-label="{{ $label ?: __('Progress') }}"
    >
        <div class="{{ $height }} {{ $toneClasses }} rounded-full transition-[width] duration-700 ease-out" style="width: {{ $percentage }}%"></div>
    </div>
</div>
