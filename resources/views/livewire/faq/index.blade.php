<div>
    {{-- Hero --}}
    <section class="relative overflow-hidden bg-linear-165 from-brand-amber/30 via-warm-cream to-brand-teal/15 pt-12 pb-14 sm:pt-16 sm:pb-20 dark:from-brand-amber/12 dark:via-zinc-950 dark:to-brand-teal/15">
        <div class="hero-mesh pointer-events-none absolute inset-x-0 -top-32 h-96 opacity-50" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -right-24 top-10 size-72 rounded-full bg-brand-teal/10 blur-3xl" aria-hidden="true"></div>

        <div class="relative mx-auto w-full max-w-4xl px-4 text-center sm:px-6">
            <x-ui.pill variant="brand" icon="chat-bubble-left-right">{{ __('Pusat Bantuan') }}</x-ui.pill>

            <h1 class="mt-5 text-3xl leading-tight font-extrabold tracking-tight text-zinc-900 sm:text-5xl dark:text-white">
                {{ __('Pertanyaan yang Sering Diajukan') }}
            </h1>

            <p class="mx-auto mt-4 max-w-2xl text-sm leading-relaxed text-zinc-600 sm:text-base dark:text-zinc-300">
                {{ __('Temukan jawaban seputar Skular, persiapan beasiswa, administrasi, esai, dan wawancara. Gunakan pencarian atau pilih kategori di bawah.') }}
            </p>

            <div class="mx-auto mt-8 max-w-2xl">
                <label for="faq-search" class="sr-only">{{ __('Cari pertanyaan atau jawaban') }}</label>

                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 start-4 flex items-center text-zinc-400" aria-hidden="true">
                        <x-icons.app-icon name="magnifying-glass" class="size-5" />
                    </span>

                    <input
                        id="faq-search"
                        type="search"
                        wire:model.live.debounce.400ms="search"
                        maxlength="100"
                        autocomplete="off"
                        placeholder="{{ __('Cari pertanyaan atau kata kunci...') }}"
                        class="h-14 w-full rounded-2xl border border-brand-teal/20 bg-white/95 pe-5 ps-12 text-sm text-zinc-800 shadow-soft transition placeholder:text-zinc-400 focus:border-brand-teal focus:outline-hidden focus:ring-4 focus:ring-brand-teal/15 sm:text-base dark:border-white/15 dark:bg-zinc-900/90 dark:text-white dark:focus:border-brand-amber dark:focus:ring-brand-amber/20"
                        data-test="faq-search"
                    >
                </div>

                @error('search')
                    <p class="mt-2 text-start text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror

                <div class="mt-2 flex min-h-5 items-center justify-center">
                    <x-loading.inline loading="faq.search" target="search,selectCategory,clearFilters" />
                </div>
            </div>
        </div>
    </section>

    <section class="relative bg-warm-cream pb-20 dark:bg-zinc-950">
        <div class="mx-auto w-full max-w-5xl px-4 sm:px-6">
            {{-- Category filters --}}
            @if ($categories->isNotEmpty())
                <nav class="-mt-7 relative z-10 rounded-2xl border border-brand-teal/12 bg-white/95 p-3 shadow-soft backdrop-blur dark:border-white/10 dark:bg-zinc-900/90" aria-label="{{ __('Filter kategori FAQ') }}">
                    <div class="flex gap-2 overflow-x-auto pb-1 sm:flex-wrap sm:overflow-visible">
                        <button
                            type="button"
                            wire:click="clearFilters"
                            aria-pressed="{{ $category === '' ? 'true' : 'false' }}"
                            @class([
                                'inline-flex shrink-0 items-center gap-2 rounded-xl px-3.5 py-2 text-sm font-semibold transition',
                                'brand-gradient text-brand-ink shadow-sm' => $category === '',
                                'bg-zinc-100 text-zinc-600 hover:bg-brand-amber/20 hover:text-brand-teal dark:bg-white/10 dark:text-zinc-300 dark:hover:text-brand-amber' => $category !== '',
                            ])
                        >
                            {{ __('Semua') }}
                        </button>

                        @foreach ($categories as $faqCategory)
                            <button
                                type="button"
                                wire:key="faq-category-{{ $faqCategory->id }}"
                                wire:click="selectCategory(@js($faqCategory->slug))"
                                aria-pressed="{{ $category === $faqCategory->slug ? 'true' : 'false' }}"
                                @class([
                                    'inline-flex shrink-0 items-center gap-2 rounded-xl px-3.5 py-2 text-sm font-semibold transition',
                                    'brand-gradient text-brand-ink shadow-sm' => $category === $faqCategory->slug,
                                    'bg-zinc-100 text-zinc-600 hover:bg-brand-amber/20 hover:text-brand-teal dark:bg-white/10 dark:text-zinc-300 dark:hover:text-brand-amber' => $category !== $faqCategory->slug,
                                ])
                            >
                                {{ $faqCategory->name }}
                                <span @class([
                                    'rounded-full px-1.5 py-0.5 text-[0.6875rem] font-bold tabular-nums',
                                    'bg-brand-ink/10 text-brand-ink' => $category === $faqCategory->slug,
                                    'bg-white/70 text-zinc-500 dark:bg-white/10 dark:text-zinc-400' => $category !== $faqCategory->slug,
                                ])>{{ $faqCategory->faqs_count }}</span>
                            </button>
                        @endforeach
                    </div>
                </nav>
            @endif

            {{-- Results summary --}}
            <div class="mt-9 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between" aria-live="polite">
                <div>
                    <h2 class="text-xl font-bold tracking-tight text-zinc-900 sm:text-2xl dark:text-white">
                        @if (filled($search))
                            {{ __('Hasil pencarian') }}
                        @elseif ($activeCategory)
                            {{ $activeCategory->name }}
                        @else
                            {{ __('Semua pertanyaan') }}
                        @endif
                    </h2>

                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400" data-test="faq-result-count">
                        @if ($faqs->total() > 0)
                            {{ __('Menampilkan :first–:last dari :total pertanyaan', [
                                'first' => $faqs->firstItem(),
                                'last' => $faqs->lastItem(),
                                'total' => $faqs->total(),
                            ]) }}
                        @else
                            {{ __('Belum ada pertanyaan yang cocok.') }}
                        @endif

                        @if (filled($search))
                            <span class="font-medium text-zinc-600 dark:text-zinc-300">“{{ $search }}”</span>
                        @endif
                    </p>
                </div>

                @if (filled($search) || $category !== '')
                    <button
                        type="button"
                        wire:click="clearFilters"
                        class="inline-flex shrink-0 items-center gap-1.5 text-sm font-semibold text-brand-teal transition hover:underline dark:text-brand-teal-light"
                    >
                        <x-icons.app-icon name="arrow-path" class="size-4" />
                        {{ __('Reset pencarian & filter') }}
                    </button>
                @endif
            </div>

            {{-- FAQ list --}}
            <div class="mt-5" wire:loading.class="opacity-60" wire:target="search,selectCategory,clearFilters">
                @if ($faqs->isEmpty())
                    <x-ui.empty-state
                        icon="face-frown"
                        :title="filled($search) || $category !== '' ? __('Tidak ada FAQ yang cocok') : __('Belum ada FAQ publik')"
                        :description="filled($search) || $category !== ''
                            ? __('Coba kata kunci lain atau tampilkan semua kategori.')
                            : __('Tim Skular belum menerbitkan pertanyaan untuk ditampilkan. Silakan kembali lagi nanti.')"
                    >
                        @if (filled($search) || $category !== '')
                            <x-actions.button type="button" variant="outline" icon="arrow-path" wire:click="clearFilters">
                                {{ __('Tampilkan semua FAQ') }}
                            </x-actions.button>
                        @else
                            <x-actions.link-button :href="route('home')" variant="outline" icon="arrow-left">
                                {{ __('Kembali ke beranda') }}
                            </x-actions.link-button>
                        @endif
                    </x-ui.empty-state>
                @else
                    <div class="flex flex-col gap-3" data-test="faq-list">
                        @foreach ($faqs as $faq)
                            <x-faq.accordion
                                :faq="$faq"
                                level="h3"
                                show-category
                                wire:key="faq-{{ $faq->id }}"
                            />
                        @endforeach
                    </div>

                    <div class="mt-8">
                        {{ $faqs->links() }}
                    </div>
                @endif
            </div>

            {{-- Helpful call to action --}}
            <div class="mt-14 overflow-hidden rounded-3xl border border-brand-teal/12 bg-linear-150 from-brand-amber/25 via-white to-brand-teal/10 p-6 shadow-soft sm:p-8 dark:border-white/10 dark:from-brand-amber/10 dark:via-zinc-900 dark:to-brand-teal/15">
                <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                    <div class="max-w-xl">
                        <h2 class="text-lg font-bold text-zinc-900 sm:text-xl dark:text-white">
                            {{ __('Belum menemukan jawabanmu?') }}
                        </h2>
                        <p class="mt-2 text-sm leading-relaxed text-zinc-600 dark:text-zinc-300">
                            {{ __('Mulai perjalanan persiapan beasiswamu, atau kembali ke beranda untuk melihat cara kerja Skular.') }}
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <x-actions.link-button :href="route('register')" iconTrailing="arrow-right">
                            {{ __('Mulai Sekarang') }}
                        </x-actions.link-button>
                        <x-actions.link-button :href="route('home')" variant="outline">
                            {{ __('Beranda') }}
                        </x-actions.link-button>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
