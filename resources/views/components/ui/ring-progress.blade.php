@props([
    'value' => 0,
    'size' => 96,
    'stroke' => 9,
    'label' => null,
    'caption' => null,
    'tone' => 'brand',
])

@php
    $percentage = max(0, min(100, (int) $value));
    $radius = round(($size - $stroke) / 2, 2);
    $circumference = round(2 * M_PI * $radius, 2);
    $offset = round($circumference * (1 - $percentage / 100), 2);
    $gradient_id = 'sk-ring-'.substr(md5((string) $tone), 0, 8);

    [$from, $to] = match ($tone) {
        'teal' => ['#56a5b2', '#277c93'],
        'mint' => ['#34d399', '#059669'],
        default => ['#f2c94c', '#f2994a'],
    };
@endphp

<div {{ $attributes->class(['relative inline-flex items-center justify-center']) }}>
    <svg
        width="{{ $size }}"
        height="{{ $size }}"
        viewBox="0 0 {{ $size }} {{ $size }}"
        role="img"
        aria-label="{{ $label ?? __('Progress') }} {{ $percentage }}%"
        class="-rotate-90"
    >
        <defs>
            <linearGradient id="{{ $gradient_id }}" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stop-color="{{ $from }}" />
                <stop offset="100%" stop-color="{{ $to }}" />
            </linearGradient>
        </defs>

        <circle
            cx="{{ $size / 2 }}"
            cy="{{ $size / 2 }}"
            r="{{ $radius }}"
            fill="none"
            stroke="currentColor"
            stroke-width="{{ $stroke }}"
            class="text-zinc-100 dark:text-white/10"
        />

        <circle
            cx="{{ $size / 2 }}"
            cy="{{ $size / 2 }}"
            r="{{ $radius }}"
            fill="none"
            stroke="url(#{{ $gradient_id }})"
            stroke-width="{{ $stroke }}"
            stroke-linecap="round"
            stroke-dasharray="{{ $circumference }}"
            stroke-dashoffset="{{ $offset }}"
            class="transition-[stroke-dashoffset] duration-700 ease-out"
        />
    </svg>

    <div class="absolute inset-0 flex flex-col items-center justify-center">
        <span class="text-lg font-bold text-zinc-900 dark:text-white">{{ $percentage }}%</span>
        @if (filled($caption))
            <span class="text-[0.625rem] font-semibold tracking-wide font-white uppercase">{{ $caption }}</span>
        @endif
    </div>
</div>
