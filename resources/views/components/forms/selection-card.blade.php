@props([
    'model',
    'value' => null,
    'title' => null,
    'description' => null,
    'icon' => null,
    'disabled' => false,
    'compact' => false,
    'grouped' => 'single',
    'change' => null,
    'name' => null,
])

@php
    /**
     * Interactive choice card for Profiling Kilat and the profiling wizard.
     *
     * `model` is an Alpine path holding the answer (for example
     * `answers.level`). `grouped="single"` renders a real radio input bound to
     * that path, so the browser itself guarantees one active card per question
     * and keyboard arrows move the selection natively. `grouped="multi"`
     * renders a checkbox derived from the same value.
     *
     * The selected look is driven by `data-selected` on the label plus
     * `group-data-[selected=true]:` variants on its children. Tailwind's
     * attribute selector wins over the default classes, so the state can never
     * flicker between two competing utilities, and the card keeps the same
     * border width and padding in every state (nothing in the grid moves).
     */
    $is_disabled = (bool) $disabled;
    $is_compact = (bool) $compact;
    $is_multi = $grouped === 'multi';
    $string_value = (string) $value;
    $json_value = json_encode($string_value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $base_classes = $is_compact
        ? 'group relative flex w-full cursor-pointer items-center gap-2.5 rounded-xl border-2 p-3 text-left select-none transition-[transform,background-color,border-color,box-shadow] duration-200 ease-out motion-reduce:transition-none'
        : 'group relative flex w-full cursor-pointer items-start gap-3 rounded-2xl border-2 p-4 text-left select-none transition-[transform,background-color,border-color,box-shadow] duration-200 ease-out motion-reduce:transition-none';

    // Hover is gated by Tailwind v4 itself, which only applies it on devices
    // whose primary input can hover. Touch devices get the selected state only.
    $interactive_classes = 'border-zinc-200/90 bg-white hover:-translate-y-0.5 hover:border-brand-orange/60 hover:bg-brand-amber/[0.07] hover:shadow-[0_14px_28px_-18px_rgba(39,124,147,0.55)] has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-offset-2 has-[:focus-visible]:outline-brand-ink data-[selected=true]:translate-y-0 data-[selected=true]:border-brand-orange/80 data-[selected=true]:bg-linear-135 data-[selected=true]:from-brand-amber data-[selected=true]:via-brand-gold data-[selected=true]:to-brand-orange data-[selected=true]:shadow-[0_18px_34px_-20px_rgba(242,153,74,0.95)] data-[selected=true]:ring-2 data-[selected=true]:ring-brand-amber/45 dark:border-white/10 dark:bg-white/5 dark:hover:border-brand-amber/60 dark:hover:bg-brand-amber/10 dark:data-[selected=true]:border-brand-amber/70 dark:data-[selected=true]:ring-brand-amber/30';

    $disabled_classes = 'cursor-not-allowed border-dashed border-zinc-300 bg-zinc-100/80 opacity-70 dark:border-white/10 dark:bg-white/[0.03]';

    $wrapper_classes = $base_classes.' '.($is_disabled ? $disabled_classes : $interactive_classes);

    $icon_classes = 'flex shrink-0 items-center justify-center rounded-xl bg-brand-amber/25 text-brand-teal transition duration-200 ease-out group-data-[selected=true]:rotate-[-6deg] group-data-[selected=true]:bg-white/85 group-data-[selected=true]:text-brand-orange motion-reduce:transition-none motion-reduce:group-data-[selected=true]:rotate-0 dark:bg-white/10 dark:text-brand-amber dark:group-data-[selected=true]:bg-white/85 dark:group-data-[selected=true]:text-brand-orange';

    $title_classes = 'block text-sm font-semibold text-zinc-800 transition-colors duration-200 group-data-[selected=true]:text-brand-ink dark:text-zinc-100';

    $description_classes = 'mt-0.5 block text-xs leading-relaxed text-zinc-500 transition-colors duration-200 group-data-[selected=true]:text-brand-ink/75 dark:text-zinc-400';

    $indicator_classes = ($is_multi ? 'rounded-md' : 'rounded-full').' flex size-5 shrink-0 items-center justify-center border-2 border-zinc-300 bg-white transition-[background-color,border-color,transform] duration-200 ease-out group-data-[selected=true]:scale-105 group-data-[selected=true]:border-brand-ink group-data-[selected=true]:bg-brand-ink motion-reduce:transition-none dark:border-zinc-600 dark:bg-transparent dark:group-data-[selected=true]:border-brand-ink dark:group-data-[selected=true]:bg-brand-ink';

    $checked_expression = $is_multi
        ? '(Array.isArray('.$model.') ? '.$model.' : []).includes('.$json_value.')'
        : 'String(('.$model.') ?? \'\') === '.$json_value;
@endphp

<label
    {{ $attributes->merge(['class' => $wrapper_classes]) }}
    @if (! $is_disabled)
        x-bind:data-selected="{{ $checked_expression }} ? 'true' : 'false'"
    @endif
    @if ($is_disabled) aria-disabled="true" @endif
>
    <input
        type="{{ $is_multi ? 'checkbox' : 'radio' }}"
        value="{{ $string_value }}"
        name="{{ $name ?? $model }}"
        @if ($is_multi)
            x-bind:checked="{{ $checked_expression }}"
            @if (filled($change))
                x-on:change="{{ $change }}({{ $json_value }})"
            @endif
        @else
            x-model="{{ $model }}"
        @endif
        @disabled($is_disabled)
        class="sr-only"
    />

    @if (filled($icon))
        <span
            class="{{ $icon_classes.' '.($is_compact ? 'size-8' : 'size-9') }}"
            aria-hidden="true"
        >
            <x-icons.app-icon :name="$icon" :class="$is_compact ? 'size-3.5' : 'size-4'" />
        </span>
    @endif

    <span class="min-w-0 flex-1">
        <span class="{{ $title_classes }}">{{ $title ?? $slot }}</span>

        @if (filled($description))
            <span class="{{ $description_classes }}">{{ $description }}</span>
        @endif
    </span>

    <span
        class="{{ $indicator_classes.' '.($is_compact ? 'mt-0' : 'mt-0.5') }}"
        aria-hidden="true"
    >
        <span class="flex scale-50 opacity-0 transition-[opacity,transform] duration-200 ease-out group-data-[selected=true]:scale-100 group-data-[selected=true]:opacity-100 motion-reduce:transition-none">
            <x-icons.app-icon name="check" class="size-3 text-brand-amber" />
        </span>
    </span>
</label>
