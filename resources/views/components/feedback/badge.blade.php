@props([
    'size' => 'sm',
])

<span {{ $attributes->class([
    'inline-flex items-center rounded-md bg-zinc-100 px-1.5 py-0.5 font-medium dark:bg-white/10',
    'text-[0.6875rem]' => $size === 'xs',
    'text-xs' => $size === 'sm',
    'text-sm' => $size === 'md',
    'text-zinc-700 dark:text-zinc-300',
]) }}>{{ $slot }}</span>
