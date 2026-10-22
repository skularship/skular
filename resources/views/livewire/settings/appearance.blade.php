<section class="w-full">
    @include('partials.settings-heading')

    <x-display.heading level="2" class="sr-only">{{ __('Appearance settings') }}</x-display.heading>

    <x-settings.layout :heading="__('Appearance')" :subheading=" __('Update the appearance settings for your account')">
        @php
            $appearanceOptions = [
                'light' => ['icon' => 'sun', 'label' => __('Light')],
                'dark' => ['icon' => 'moon', 'label' => __('Dark')],
                'system' => ['icon' => 'computer-desktop', 'label' => __('System')],
            ];
        @endphp

        <div
            x-data
            class="inline-flex w-full gap-1 rounded-lg border border-zinc-200 bg-zinc-100 p-1 dark:border-zinc-700 dark:bg-white/5"
            role="radiogroup"
            aria-label="{{ __('Appearance') }}"
        >
            @foreach ($appearanceOptions as $value => $option)
                <button
                    type="button"
                    role="radio"
                    x-on:click="$store.appearance.set('{{ $value }}')"
                    x-bind:aria-checked="$store.appearance.mode === '{{ $value }}'"
                    x-bind:class="$store.appearance.mode === '{{ $value }}'
                        ? 'bg-white text-zinc-900 shadow-xs dark:bg-zinc-700 dark:text-white'
                        : 'text-zinc-500 hover:text-zinc-800 dark:hover:text-white'"
                    class="flex flex-1 items-center justify-center gap-2 rounded-md px-3 py-2 text-sm font-medium transition focus:outline-hidden focus-visible:ring-2 focus-visible:ring-accent"
                >
                    <x-icons.app-icon name="{{ $option['icon'] }}" class="size-4" />
                    {{ $option['label'] }}
                </button>
            @endforeach
        </div>
    </x-settings.layout>
</section>
