{{-- Five-segment meter of the final adaptive level (<x-tryout.level-meter>). Props: level (1-5|null), max. --}}
@props([
    'level' => null,
    'max' => 5,
])

<div {{ $attributes->class(['flex gap-1']) }} role="img" aria-label="{{ __('Level :level dari :max', ['level' => $level ?? 0, 'max' => $max]) }}">
    @for ($i = 1; $i <= $max; $i++)
        <span @class([
            'h-2 flex-1 rounded-full',
            'bg-brand-teal' => $level !== null && $i <= $level,
            'bg-zinc-200' => $level === null || $i > $level,
        ])></span>
    @endfor
</div>
