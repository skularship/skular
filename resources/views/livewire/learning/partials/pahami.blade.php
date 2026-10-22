@php
    $has_material = filled($module->summary_material) || filled($module->inti_material) || (is_array($module->insights) && $module->insights !== []);
@endphp

<div class="sk-rise flex flex-col gap-6">
    @if ($module->hasVideo())
        <x-ui.card padding="none" class="overflow-hidden">
            <div class="relative aspect-video w-full bg-zinc-900">
                <iframe
                    class="absolute inset-0 h-full w-full"
                    src="{{ $module->link_video }}"
                    title="{{ __('Video materi — :title', ['title' => $module->title]) }}"
                    loading="lazy"
                    allowfullscreen
                    allow="accelerometer; encrypted-media; picture-in-picture; fullscreen"
                ></iframe>
            </div>
        </x-ui.card>
    @endif

    <div class="flex flex-col gap-4" data-test="pahami-header">
        <div class="flex items-center gap-3">
            <x-ui.icon-badge icon="book-open-text" tone="teal" solid />
            <div>
                <span class="text-xs font-bold tracking-widest text-brand-teal uppercase dark:text-brand-teal-light">{{ __('Pahami') }}</span>
                <h2 class="text-xl font-extrabold tracking-tight text-zinc-900 dark:text-white">{{ __('Materi utama') }}</h2>
            </div>
        </div>
    </div>

    @if ($has_material)
        <x-ui.card padding="lg" class="flex flex-col gap-8">
            @if (filled($module->summary_material))
                <section class="flex flex-col gap-3">
                    <h3 class="inline-flex items-center gap-2 text-base font-bold text-zinc-900 dark:text-white">
                        <x-icons.app-icon name="document-text" class="size-4 text-brand-teal dark:text-brand-teal-light" />
                        {{ __('Ringkasan materi') }}
                    </h3>

                    <p class="max-w-3xl border-s-4 border-brand-amber/60 ps-4 text-[0.9375rem] leading-relaxed whitespace-pre-line text-zinc-600 dark:border-brand-amber/40 dark:text-zinc-300">
                        {{ $module->summary_material }}
                    </p>
                </section>
            @endif

            @if (is_array($module->insights) && $module->insights !== [])
                <section class="flex flex-col gap-3">
                    <h3 class="inline-flex items-center gap-2 text-base font-bold text-zinc-900 dark:text-white">
                        <x-icons.app-icon name="light-bulb" class="size-4 text-brand-gold" />
                        {{ __('Insight penting') }}
                    </h3>

                    <ul class="grid gap-3 sm:grid-cols-2">
                        @foreach ($module->insights as $insight)
                            <li class="flex items-start gap-3 rounded-2xl border border-brand-amber/35 bg-brand-amber/10 px-4 py-3 text-sm text-zinc-700 dark:border-brand-amber/20 dark:text-zinc-200">
                                <span class="flex size-6 shrink-0 items-center justify-center rounded-full brand-gradient text-[0.6875rem] font-bold text-brand-ink">{{ $loop->iteration }}</span>
                                <span>{{ $insight }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if (filled($module->inti_material))
                <section class="flex flex-col gap-3 rounded-3xl border border-brand-teal/25 bg-brand-teal/5 p-5 dark:border-brand-teal-light/20 dark:bg-brand-teal/10">
                    <h3 class="inline-flex items-center gap-2 text-base font-bold text-brand-teal dark:text-brand-teal-light">
                        <x-icons.app-icon name="bolt" class="size-4" />
                        {{ __('Inti materi') }}
                    </h3>

                    <p class="max-w-3xl text-[0.9375rem] leading-relaxed whitespace-pre-line text-zinc-700 dark:text-zinc-200">
                        {{ $module->inti_material }}
                    </p>
                </section>
            @endif
        </x-ui.card>
    @else
        <x-ui.empty-state
            icon="book-open-text"
            :title="__('Materi belum tersedia')"
            :description="__('Admin belum menambahkan materi untuk modul ini. Kamu bisa lanjut ke bagian berikutnya.')"
        />
    @endif

    @if (filled($module->introduction))
        @include('livewire.learning.partials.module-introduction', ['variant' => 'quiz'])
    @endif

    <div class="flex flex-wrap items-center justify-between gap-3">
        <x-actions.button type="button" variant="outline" icon="arrow-left" wire:click="previousSection" loading="module.section">
            {{ __('Kembali') }}
        </x-actions.button>

        <x-actions.button type="button" iconTrailing="arrow-right" wire:click="nextSection" loading="module.section" data-test="next-section">
            {{ __('Lanjut') }}
        </x-actions.button>
    </div>
</div>
