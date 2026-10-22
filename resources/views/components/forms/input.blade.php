@props([
    'name' => null,
    'label' => null,
    'type' => 'text',
    'value' => null,
    'viewable' => false,
])

@php
    $name ??= $attributes->whereStartsWith('wire:model')->first();

    $fieldId = $attributes->get('id') ?? uniqid('input-');

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

    <div @if ($viewable) x-data="{ visible: false }" @endif class="relative">
        <input
            {{ $attributes->merge($extraAttributes)->merge(['id' => $fieldId])->class([
                'block h-10 w-full appearance-none rounded-lg border bg-white py-2 ps-3 pe-3 text-base leading-[1.375rem] shadow-none transition sm:text-sm',
                'text-zinc-700 placeholder:text-zinc-400 dark:bg-white/10 dark:text-zinc-300 dark:placeholder:text-zinc-400',
                'focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-accent focus:ring-offset-accent-foreground',
                'disabled:cursor-default disabled:opacity-50',
                $viewable ? 'pe-10' : '',
                $hasError
                    ? 'border-red-500 focus:ring-red-500 dark:border-red-500/60'
                    : 'border-zinc-200 dark:border-zinc-600',
            ]) }}
            @if ($viewable)
                :type="visible ? 'text' : '{{ $type }}'"
            @else
                type="{{ $type }}"
            @endif
            @if ($value !== null) value="{{ $value }}" @endif
        />

        @if ($viewable)
            <button
                type="button"
                x-on:click="visible = ! visible"
                x-bind:aria-label="visible ? '{{ __('Hide') }}' : '{{ __('Show') }}'"
                class="absolute end-2 top-1/2 -translate-y-1/2 rounded-md p-1 text-zinc-400 transition hover:text-zinc-700 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-accent dark:text-zinc-500 dark:hover:text-zinc-200"
            >
                <x-icons.app-icon name="eye" class="size-4" x-show="! visible" />
                <x-icons.app-icon name="eye-slash" class="size-4" x-show="visible" x-cloak />
            </button>
        @endif
    </div>

    @if ($hasError)
        <p class="mt-1.5 text-sm text-red-600 dark:text-red-400" data-test="error-{{ $name }}">
            {{ $errors->first($name) }}
        </p>
    @endif
</div>
