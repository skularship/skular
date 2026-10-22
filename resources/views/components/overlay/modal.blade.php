@props([
    'name' => null,
    'model' => null,
    'show' => false,
    'focusable' => false,
])

@php
    $panelClass = $attributes->get('class') ?: 'max-w-md';

    $attributes = $attributes->except(['class']);

    $openExpression = filled($model)
        ? '$wire.entangle(\''.$model.'\')'
        : ($show ? 'true' : 'false');
@endphp

<div
    x-data="{
        open: {{ $openExpression }},
        name: @js($name),
        init() {
            this.$watch('open', (open) => {
                if (open) {
                    this.$nextTick(() => this.$refs.panel?.focus());
                }
            });
        },
        close() {
            this.open = false;
            this.$dispatch('close');
        },
    }"
    x-show="open"
    x-cloak
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    x-on:keydown.escape.window="open && close()"
    x-on:open-modal.window="event.detail === name && (open = true)"
    x-on:close-modal.window="event.detail === name && close()"
    {{ $attributes->class(['fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6']) }}
>
    <div class="absolute inset-0 bg-zinc-950/50 backdrop-blur-sm" x-on:click="close()"></div>

    <div
        x-ref="panel"
        tabindex="-1"
        class="relative z-10 max-h-[calc(100dvh-2rem)] w-full overflow-y-auto overscroll-contain rounded-xl bg-white p-5 shadow-lg ring ring-black/5 focus:outline-hidden sm:p-6 dark:bg-zinc-800 dark:ring-zinc-700 {{ $panelClass }}"
    >
        {{ $slot }}

        <button
            type="button"
            x-on:click="close()"
            aria-label="{{ __('Close modal') }}"
            class="absolute end-2 top-2 rounded-md p-1.5 text-zinc-400 transition hover:bg-zinc-100 hover:text-zinc-700 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-accent dark:text-zinc-500 dark:hover:bg-white/10 dark:hover:text-white"
        >
            <x-icons.app-icon name="x-mark" class="size-5" />
        </button>
    </div>
</div>
