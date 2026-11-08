{{-- Pahami step: video, Kata Pengantar, Inti Materi columns (falls back to the legacy inti_material text), then Ringkasan and Insight. --}}
@php
    // modules.insights is a JSON list written by the admin editor. Only
    // non-empty text items are shown, so a blank or malformed entry can never
    // render an empty card or break the page.
    $insights = collect(is_array($module->insights) ? $module->insights : [])
        ->filter(fn (mixed $insight): bool => is_string($insight) && filled($insight))
        ->values();

    $core_sections = $module->filledCoreSections();
    $has_core = $core_sections->isNotEmpty() || filled($module->inti_material);
    $has_summary = filled($module->summary_material) || $insights->isNotEmpty();
    $has_material = $has_core || $has_summary;
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

    @if (filled($module->introduction))
        @include('livewire.learning.partials.module-introduction', ['variant' => 'pahami'])
    @endif

    @if ($has_core)
        <section class="flex flex-col gap-4" data-test="inti-materi" aria-labelledby="inti-materi-heading">
            <h3 id="inti-materi-heading" class="inline-flex items-center gap-2 text-lg font-extrabold tracking-tight text-zinc-900">
                <x-icons.app-icon name="bolt" class="size-5 text-brand-teal" />
                {{ __('Inti Materi') }}
            </h3>

            @if ($core_sections->isNotEmpty())
                @if ($core_sections->count() > 1)
                    {{-- A short table of contents keeps long material easy to skim. --}}
                    <nav aria-label="{{ __('Daftar inti materi') }}" class="-mx-1 flex gap-2 overflow-x-auto px-1 pb-1">
                        @foreach ($core_sections as $core_section)
                            <a
                                href="#inti-{{ $core_section->id }}"
                                class="inline-flex min-h-11 shrink-0 items-center gap-2 rounded-full border border-brand-teal/20 bg-white px-3.5 py-2 text-xs font-semibold text-zinc-600 transition hover:border-brand-teal hover:text-brand-teal focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-teal"
                            >
                                <span class="flex size-5 items-center justify-center rounded-full bg-brand-teal/10 text-[0.625rem] font-bold text-brand-teal">{{ $loop->iteration }}</span>
                                {{ \Illuminate\Support\Str::limit($core_section->subtitle, 40) }}
                            </a>
                        @endforeach
                    </nav>
                @endif

                <ol class="flex flex-col gap-4">
                    @foreach ($core_sections as $core_section)
                        <li id="inti-{{ $core_section->id }}" class="scroll-mt-24 rounded-3xl border border-brand-teal/20 bg-white p-5 shadow-soft sm:p-6" data-test="inti-materi-kolom">
                            <h4 class="flex items-start gap-3 text-base font-bold text-zinc-900">
                                <span class="flex size-7 shrink-0 items-center justify-center rounded-full brand-gradient text-xs font-bold text-brand-ink">{{ $loop->iteration }}</span>
                                <span class="min-w-0 pt-0.5 break-words">{{ $core_section->subtitle }}</span>
                            </h4>

                            <p class="mt-3 max-w-3xl text-[0.9375rem] leading-relaxed break-words whitespace-pre-line text-zinc-700 sm:ps-10">{{ $core_section->body }}</p>
                        </li>
                    @endforeach
                </ol>
            @else
                <div class="rounded-3xl border border-brand-teal/25 bg-brand-teal/5 p-5">
                    <p class="max-w-3xl text-[0.9375rem] leading-relaxed whitespace-pre-line text-zinc-700">{{ $module->inti_material }}</p>
                </div>
            @endif
        </section>
    @endif

    @if ($has_summary)
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

            @if ($insights->isNotEmpty())
                <section class="flex flex-col gap-3">
                    <h3 class="inline-flex items-center gap-2 text-base font-bold text-zinc-900 dark:text-white">
                        <x-icons.app-icon name="light-bulb" class="size-4 text-brand-gold" />
                        {{ __('Insight penting') }}
                    </h3>

                    <ul class="grid gap-3 sm:grid-cols-2">
                        @foreach ($insights as $insight)
                            <li class="flex items-start gap-3 rounded-2xl border border-brand-amber/35 bg-brand-amber/10 px-4 py-3 text-sm text-zinc-700 dark:border-brand-amber/20 dark:text-zinc-200">
                                <span class="flex size-6 shrink-0 items-center justify-center rounded-full brand-gradient text-[0.6875rem] font-bold text-brand-ink">{{ $loop->iteration }}</span>
                                <span>{{ $insight }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

        </x-ui.card>
    @endif

    @unless ($has_material)
        <x-ui.empty-state
            icon="book-open-text"
            :title="__('Materi belum tersedia')"
            :description="__('Admin belum menambahkan materi untuk modul ini. Kamu bisa lanjut ke bagian berikutnya.')"
        />
    @endunless

    <div class="flex flex-wrap items-center justify-between gap-3">
        <x-actions.button type="button" variant="outline" icon="arrow-left" wire:click="previousSection" loading="module.section">
            {{ __('Kembali') }}
        </x-actions.button>

        <x-actions.button type="button" iconTrailing="arrow-right" wire:click="nextSection" loading="module.section" data-test="next-section">
            {{ __('Lanjut') }}
        </x-actions.button>
    </div>
</div>
