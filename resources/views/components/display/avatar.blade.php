@props([
    'initials' => null,
    'name' => null,
    'size' => 'sm',
])

<span {{ $attributes->class([
    'inline-flex shrink-0 items-center justify-center overflow-hidden rounded-full bg-zinc-200 font-medium text-zinc-800 dark:bg-zinc-600 dark:text-white',
    'size-6 text-[0.625rem]' => $size === 'xs',
    'size-8 text-xs' => $size === 'sm',
    'size-10 text-sm' => $size === 'md',
]) }}>{{ $initials ?: \Illuminate\Support\Str::of($name ?? '')->explode(' ')->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))->take(2)->implode('') }}</span>
