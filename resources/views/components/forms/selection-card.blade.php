@props([
    'value' => null,
    'selected' => false,
    'title' => null,
    'description' => null,
    'icon' => null,
    'disabled' => false,
])

@php
    $is_selected = (bool) $selected;
    $is_disabled = (bool) $disabled;
@endphp

<label
    {{ $attributes->class([
        'group relative flex cursor-pointer flex-col gap-1 rounded-2xl border-2 bg-white/70 p-4 text-left transition duration-150 outline-none select-none',
        'hover:-translate-y-0.5 hover:border-brand-orange/60 hover:bg-brand-amber/15 hover:shadow-sm focus-within:border-brand-orange focus-within:ring-2 focus-within:ring-brand-orange/30',
        'border-brand-orange bg-brand-amber/20 shadow-sm' => $is_selected && ! $is_disabled,
        'border-zinc-200' => ! $is_selected && ! $is_disabled,
        'cursor-not-allowed border-zinc-200 bg-zinc-100/80 opacity-60 hover:translate-y-0 hover:border-zinc-200 hover:bg-zinc-100/80 dark:border-zinc-700 dark:bg-white/5' => $is_disabled,
    ])->merge(['aria-pressed' => $is_selected ? 'true' : 'false']) }}
>
    <input
        type="checkbox"
        value="{{ $value }}"
        @checked($is_selected)
        @disabled($is_disabled)
        {{ $attributes->only('wire:model', 'wire:model.live', 'name') }}
        class="peer sr-only"
    />

    <span class="flex items-center justify-between gap-3">
        <span class="inline-flex size-8 shrink-0 items-center justify-center rounded-xl @if ($is_selected) bg-brand-orange text-white @else bg-brand-amber/25 text-brand-teal @endif transition-colors">
            @if (filled($icon))
                <x-icons.app-icon :name="$icon" class="size-4" />
            @else
                <x-icons.app-icon :name="$is_selected ? 'check' : 'plus'" class="size-4" />
            @endif
        </span>

        <span
            @class([
                'flex size-5 shrink-0 items-center justify-center rounded-full border-2 transition',
                'border-brand-orange bg-brand-orange' => $is_selected,
                'border-zinc-300 bg-transparent' => ! $is_selected,
            ])
        >
            @if ($is_selected)
                <x-icons.app-icon name="check" class="size-3 text-white" />
            @endif
        </span>
    </span>

    <span @class([
        'text-sm font-semibold transition-colors',
        'text-brand-teal dark:text-brand-teal-light' => $is_selected,
        'text-zinc-800 dark:text-zinc-100' => ! $is_selected,
    ])>{{ $title ?? $slot }}</span>

    @if (filled($description))
        <span class="text-xs leading-relaxed text-zinc-500 dark:text-zinc-400">{{ $description }}</span>
    @endif
</label>
