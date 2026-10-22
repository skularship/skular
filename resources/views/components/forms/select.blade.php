@props([
    'name' => null,
    'label' => null,
    'hint' => null,
])

@php
    $name ??= $attributes->whereStartsWith('wire:model')->first();

    $fieldId = $attributes->get('id') ?? uniqid('select-');

    $hasError = filled($name) && $errors->has($name);

    $extraAttributes = array_filter([
        'name' => $name,
    ], fn ($attribute) => filled($attribute));
@endphp

<div class="w-full">
    @if (filled($label))
        <label for="{{ $fieldId }}" class="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
            {{ $label }}
        </label>
    @endif

    <select
        {{ $attributes->merge($extraAttributes)->merge(['id' => $fieldId])->class([
            'block h-10 w-full appearance-none rounded-lg border bg-white px-3 py-2 text-base leading-[1.375rem] transition sm:text-sm',
            'text-zinc-700 dark:bg-white/10 dark:text-zinc-300',
            'focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-accent focus:ring-offset-accent-foreground',
            'disabled:cursor-default disabled:opacity-50',
            $hasError
                ? 'border-red-500 focus:ring-red-500 dark:border-red-500/60'
                : 'border-zinc-200 dark:border-zinc-600',
        ]) }}
    >
        {{ $slot }}
    </select>

    @if ($hasError)
        <p class="mt-1.5 text-sm text-red-600 dark:text-red-400" data-test="error-{{ $name }}">
            {{ $errors->first($name) }}
        </p>
    @elseif (filled($hint))
        <p class="mt-1.5 text-sm text-zinc-500 dark:text-zinc-400">{{ $hint }}</p>
    @endif
</div>
