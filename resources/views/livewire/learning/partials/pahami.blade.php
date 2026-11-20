{{--
    Pahami step: video, Kata Pengantar, Inti Materi, then Ringkasan and Insight.
    Inti Materi reads like one document sheet (one column, max 68ch): every column is a document part
    ("Bagian X dari N", deep teal subtitle) separated by a thin rule, with a table of contents that is
    a chip row on mobile, a sticky chip bar on lg and a sticky side list on xl (active part follows the
    scroll via Alpine + IntersectionObserver). Bodies are Markdown or rich editor HTML (body_format),
    both rendered by x-learning.rich-content. Modules without columns fall back to the legacy inti_material text.
--}}
@php
    // modules.insights is a JSON list written by the admin editor. Only
    // non-empty text items are shown, so a blank or malformed entry can never
    // render an empty card or break the page.
    $insights = collect(is_array($module->insights) ? $module->insights : [])
        ->filter(fn (mixed $insight): bool => is_string($insight) && filled($insight))
        ->values();

    $core_sections = $module->filledCoreSections();
    $has_core = $core_sections->isNotEmpty() || filled($module->inti_material);
    $core_count = $core_sections->count();

    // Rough reading time at 200 words a minute, from the visible text only.
    $core_words = $core_sections->isNotEmpty()
        ? $core_sections->sum(fn (\App\Models\ModuleCoreSection $section): int => str_word_count(strip_tags((string) $section->body)))
        : str_word_count(strip_tags((string) $module->inti_material));
    $reading_minutes = max(1, (int) round($core_words / 200));
    $has_summary = filled($module->summary_material) || $insights->isNotEmpty();
    $has_material = $has_core || $has_summary;
@endphp

<div class="sk-rise flex flex-col gap-6">
    {{-- The video slot is always present: the admin fills an optional YouTube
         link, and a module without one says so instead of leaving a gap. --}}
    @if ($module->hasVideo())
        <x-ui.card padding="none" class="overflow-hidden" data-test="module-video">
            <div class="relative aspect-video w-full bg-zinc-900">
                <iframe
                    class="absolute inset-0 h-full w-full"
                    src="{{ $module->videoEmbedUrl() }}"
                    title="{{ __('Video materi — :title', ['title' => $module->title]) }}"
                    loading="lazy"
                    allowfullscreen
                    referrerpolicy="strict-origin-when-cross-origin"
                    allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share; fullscreen"
                ></iframe>
            </div>
        </x-ui.card>
    @else
        <div
            class="flex items-center gap-3 rounded-3xl border border-dashed border-zinc-300 bg-zinc-50 px-5 py-4 text-sm font-medium text-zinc-500"
            data-test="module-video-empty"
        >
            <x-icons.app-icon name="video-camera-slash" class="size-5 shrink-0 text-zinc-400" />
            {{ __('Tidak ada materi video') }}
        </div>
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
        <section
            class="flex flex-col gap-4 xl:grid xl:grid-cols-[11.5rem_minmax(0,1fr)] xl:items-start xl:gap-8"
            data-test="inti-materi"
            aria-labelledby="inti-materi-heading"
            @if ($core_count > 1)
                x-data="{
                    active: @js('inti-'.$core_sections->first()->id),
                    observer: null,
                    init() {
                        const parts = [...this.$el.querySelectorAll('[data-inti-part]')];
                        this.observer = new IntersectionObserver((entries) => {
                            const visible = entries.filter((entry) => entry.isIntersecting)
                                .sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top);
                            if (visible.length) { this.active = visible[0].target.id; }
                        }, { rootMargin: '-15% 0px -65% 0px' });
                        parts.forEach((part) => this.observer.observe(part));
                    },
                    destroy() { this.observer?.disconnect(); },
                }"
            @endif
        >
            @if ($core_count > 1)
                {{-- Table of contents: chips on mobile, sticky chip bar on lg, sticky side list on xl. --}}
                <nav
                    aria-label="{{ __('Daftar inti materi') }}"
                    class="-mx-1 flex gap-2 overflow-x-auto px-1 pb-1 lg:sticky lg:top-3 lg:z-10 lg:mx-0 lg:rounded-2xl lg:border lg:border-zinc-200/80 lg:bg-white/95 lg:p-2 lg:shadow-sm lg:backdrop-blur xl:top-6 xl:order-first xl:flex-col xl:gap-1 xl:overflow-visible xl:border-0 xl:bg-transparent xl:p-0 xl:shadow-none xl:backdrop-blur-none"
                    data-test="inti-materi-daftar-isi"
                >
                    <p class="hidden px-3 pb-2 text-[0.6875rem] font-bold tracking-widest text-zinc-500 uppercase xl:block">{{ __('Daftar isi') }}</p>
                    @foreach ($core_sections as $core_section)
                        <a
                            href="#inti-{{ $core_section->id }}"
                            x-on:click="active = @js('inti-'.$core_section->id)"
                            x-bind:aria-current="active === @js('inti-'.$core_section->id) ? 'true' : null"
                            x-bind:class="active === @js('inti-'.$core_section->id) ? 'border-brand-teal bg-brand-teal/10 text-brand-teal-deep xl:bg-brand-teal/10' : 'border-zinc-200 text-zinc-600'"
                            class="inline-flex min-h-11 shrink-0 items-center gap-2 rounded-full border bg-white px-3.5 py-2 text-xs font-semibold transition hover:border-brand-teal hover:text-brand-teal focus-visible:ring-2 focus-visible:ring-brand-teal focus-visible:outline-hidden xl:min-h-0 xl:w-full xl:items-start xl:rounded-xl xl:border-0 xl:border-s-2 xl:bg-transparent xl:px-3 xl:py-2 xl:text-[0.8125rem] xl:leading-snug"
                        >
                            <span class="text-[0.6875rem] font-bold tabular-nums text-brand-teal xl:pt-px">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                            <span class="xl:hidden">{{ \Illuminate\Support\Str::limit($core_section->subtitle, 40) }}</span>
                            <span class="hidden min-w-0 wrap-break-word xl:inline">{{ $core_section->subtitle }}</span>
                        </a>
                    @endforeach
                </nav>
            @endif

            {{-- One document sheet: no cards inside, so the reading flow is never cut. --}}
            <article class="min-w-0 rounded-3xl border border-zinc-200/80 bg-white px-5 py-8 sm:px-10 sm:py-12">
                <div class="mx-auto max-w-[68ch]">
                    <header class="border-b border-zinc-200 pb-6">
                        <span class="inline-flex items-center gap-1.5 text-xs font-bold tracking-widest text-brand-teal uppercase">
                            <x-icons.app-icon name="bolt" class="size-4" />
                            {{ __('Inti Materi') }}
                        </span>
                        <h3 id="inti-materi-heading" class="mt-2 text-2xl font-extrabold tracking-tight text-zinc-900 sm:text-[1.75rem] sm:leading-tight">{{ $module->title }}</h3>
                        <p class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-zinc-500">
                            @if ($core_count > 1)
                                <span class="inline-flex items-center gap-1.5">
                                    <x-icons.app-icon name="list-bullet" class="size-4" />
                                    {{ __(':count bagian', ['count' => $core_count]) }}
                                </span>
                            @endif
                            <span class="inline-flex items-center gap-1.5">
                                <x-icons.app-icon name="clock" class="size-4" />
                                {{ __('± :minutes menit baca', ['minutes' => $reading_minutes]) }}
                            </span>
                        </p>
                    </header>

                    @if ($core_sections->isNotEmpty())
                        <ol class="list-none">
                            @foreach ($core_sections as $core_section)
                                <li
                                    id="inti-{{ $core_section->id }}"
                                    class="scroll-mt-24 pt-10 [&+&]:mt-12 [&+&]:border-t [&+&]:border-zinc-200 [&+&]:pt-12"
                                    data-test="inti-materi-kolom"
                                    data-inti-part
                                >
                                    @if ($core_count > 1)
                                        <p class="text-xs font-semibold tracking-widest text-zinc-500 uppercase">{{ __('Bagian :current dari :total', ['current' => $loop->iteration, 'total' => $core_count]) }}</p>
                                    @endif
                                    <h4 class="mt-2 text-2xl leading-tight font-extrabold tracking-tight wrap-break-word text-brand-teal-deep">{{ $core_section->subtitle }}</h4>

                                    {{-- A top-level heading in the body nests under this <h4> subtitle, so it starts at <h5>. --}}
                                    <x-learning.rich-content :content="$core_section->body" :format="$core_section->body_format" :level="5" class="mt-5" />
                                </li>
                            @endforeach
                        </ol>
                    @else
                        <x-learning.rich-content :content="$module->inti_material" :level="4" class="pt-8" />
                    @endif

                    <div class="mt-12 flex items-start gap-3 rounded-2xl bg-brand-teal/5 px-4 py-3.5" data-test="inti-materi-selesai">
                        <x-icons.app-icon name="check-circle" class="mt-0.5 size-5 text-brand-teal" />
                        <div class="text-sm">
                            <p class="font-semibold text-zinc-900">{{ __('Kamu sudah sampai di akhir inti materi.') }}</p>
                            <p class="mt-0.5 text-zinc-600">
                                {{ $has_summary ? __('Baca ringkasan di bawah untuk mengunci poin pentingnya.') : __('Tekan Lanjut kalau kamu sudah siap.') }}
                            </p>
                        </div>
                    </div>
                </div>
            </article>
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

                    <div class="border-s-4 border-brand-amber/60 ps-4">
                        <x-learning.rich-content :content="$module->summary_material" :level="4" />
                    </div>
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
