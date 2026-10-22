@props([
    'loading' => null,
    'message' => null,
    'target' => null,
    'detail' => null,
    'delay' => true,
    'tone' => 'teal',
])

@php
    $copy = $message;

    if (blank($copy) && filled($loading)) {
        $copy = config('loading.copy', [])[$loading] ?? null;
    }

    $copy = filled($copy) ? __($copy) : __('Lagi diproses…');

    $toneClasses = match ($tone) {
        'amber' => 'text-brand-gold dark:text-brand-amber',
        'peach' => 'text-brand-orange dark:text-brand-peach',
        default => 'text-brand-teal dark:text-brand-teal-light',
    };

    $dotColors = match ($tone) {
        'amber' => ['bg-brand-amber', 'bg-brand-gold', 'bg-brand-orange'],
        'peach' => ['bg-brand-orange', 'bg-brand-peach', 'bg-brand-amber'],
        default => ['bg-brand-teal-light', 'bg-brand-teal', 'bg-brand-amber'],
    };
@endphp

<p
    {{ $attributes->class(['inline-flex flex-wrap items-center gap-2 text-xs font-semibold', $toneClasses]) }}
    @if ($delay) wire:loading.delay @else wire:loading @endif
    @if (filled($target)) wire:target="{{ $target }}" @endif
    role="status"
    aria-live="polite"
>
    <span class="sk-loading-dots" aria-hidden="true">
        @foreach ($dotColors as $color)
            <span class="{{ $color }}"></span>
        @endforeach
    </span>

    <span>{{ $copy }}</span>

    @if (filled($detail))
        <span class="font-normal text-zinc-500 dark:text-zinc-400">{{ $detail }}</span>
    @endif
</p>
