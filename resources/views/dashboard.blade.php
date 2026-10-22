<x-layouts::app :title="__('Dashboard')">
    <div class="brand-grid relative flex h-full w-full flex-1 flex-col items-center justify-center gap-4 overflow-hidden rounded-3xl border border-brand-teal/10 bg-linear-150 from-brand-amber/12 via-warm-cream to-brand-teal/10 p-8 text-center dark:border-white/10 dark:from-brand-amber/10 dark:via-zinc-900 dark:to-brand-teal/10">
        <span class="brand-gradient inline-flex size-14 items-center justify-center rounded-2xl text-brand-ink shadow-sm">
            <x-icons.app-icon name="rocket-launch" class="size-7" />
        </span>

        <x-display.heading level="1" size="2xl">{{ __('Selamat datang di Skular') }}</x-display.heading>

        <x-display.text class="max-w-md text-zinc-500 dark:text-zinc-400">
            {{ __('Ruang belajarmu dibuka lewat komponen Livewire di bawah ini.') }}
        </x-display.text>
    </div>
</x-layouts::app>
