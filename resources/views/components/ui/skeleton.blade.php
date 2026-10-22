@props([
    'lines' => 1,
    'height' => 'h-4',
    'rounded' => 'rounded-xl',
])

<div {{ $attributes->class(['flex flex-col gap-2']) }} aria-hidden="true">
    @foreach (range(1, max((int) $lines, 1)) as $line)
        <span @class([
            'sk-skeleton block',
            $height,
            $rounded,
            'w-full' => $loop->last,
            'w-3/5' => ! $loop->last,
        ])></span>
    @endforeach
</div>
