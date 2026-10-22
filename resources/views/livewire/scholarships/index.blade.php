<div class="mx-auto flex w-full max-w-6xl flex-col gap-6">
    <x-ui.section-heading
        :eyebrow="__('Jelajahi beasiswa')"
        eyebrow-icon="magnifying-glass"
        level="1"
        :title="__('Semua Beasiswa')"
        :description="__('Cari beasiswa yang cocok, lalu tambahkan ke daftar persiapanmu.')"
    >
        <x-ui.pill :variant="$at_limit ? 'warning' : 'success'" icon="academic-cap" data-test="selection-count">
            {{ __(':count dari :max beasiswa dipilih', ['count' => $selected_count, 'max' => $max_selections]) }}
        </x-ui.pill>

        <x-actions.link-button :href="route('scholarships.tracker')" wire:navigate variant="outline" icon="document-arrow-up">
            {{ __('Beasiswa Saya') }}
        </x-actions.link-button>
    </x-ui.section-heading>

    @if ($at_limit)
        <x-feedback.callout variant="warning" :heading="__('Kuota pilihan penuh')" data-test="limit-callout">
            {{ __('Kamu sudah memilih :max beasiswa. Selesaikan atau hapus salah satu dulu sebelum menambah yang baru.', ['max' => $max_selections]) }}
        </x-feedback.callout>
    @endif

    <x-ui.card variant="teal" class="flex flex-col gap-4">
        <div class="flex items-center gap-2">
            <x-ui.icon-badge icon="funnel" tone="teal" size="sm" />
            <p class="text-sm font-bold text-zinc-900 dark:text-white">{{ __('Filter & pencarian') }}</p>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <label class="flex flex-col gap-1 lg:col-span-2">
                <span class="text-xs font-semibold text-zinc-500 dark:text-zinc-400">{{ __('Cari') }}</span>
                <input
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="{{ __('Nama beasiswa atau institusi...') }}"
                    class="block h-11 w-full rounded-xl border border-zinc-200 bg-white px-3 text-base text-zinc-700 focus:outline-hidden focus:ring-2 focus:ring-accent sm:h-10 sm:text-sm dark:border-zinc-600 dark:bg-white/10 dark:text-zinc-200"
                    data-test="search-input"
                >
            </label>

            <label class="flex flex-col gap-1">
                <span class="text-xs font-semibold text-zinc-500 dark:text-zinc-400">{{ __('Jenjang') }}</span>
                <select wire:model.live="level" class="block h-11 w-full rounded-xl border border-zinc-200 bg-white px-3 text-base text-zinc-700 focus:outline-hidden focus:ring-2 focus:ring-accent sm:h-10 sm:text-sm dark:border-zinc-600 dark:bg-white/10 dark:text-zinc-200">
                    <option value="">{{ __('Semua jenjang') }}</option>
                    @foreach ($levels as $option)
                        <option value="{{ $option->value }}">{{ $option->label() }}</option>
                    @endforeach
                </select>
            </label>

            <label class="flex flex-col gap-1">
                <span class="text-xs font-semibold text-zinc-500 dark:text-zinc-400">{{ __('Cakupan') }}</span>
                <select wire:model.live="scope" class="block h-11 w-full rounded-xl border border-zinc-200 bg-white px-3 text-base text-zinc-700 focus:outline-hidden focus:ring-2 focus:ring-accent sm:h-10 sm:text-sm dark:border-zinc-600 dark:bg-white/10 dark:text-zinc-200">
                    <option value="">{{ __('Semua cakupan') }}</option>
                    @foreach ($scopes as $option)
                        <option value="{{ $option->value }}">{{ $option->label() }}</option>
                    @endforeach
                </select>
            </label>

            <label class="flex flex-col gap-1">
                <span class="text-xs font-semibold text-zinc-500 dark:text-zinc-400">{{ __('Lokasi') }}</span>
                <select wire:model.live="location" class="block h-11 w-full rounded-xl border border-zinc-200 bg-white px-3 text-base text-zinc-700 focus:outline-hidden focus:ring-2 focus:ring-accent sm:h-10 sm:text-sm dark:border-zinc-600 dark:bg-white/10 dark:text-zinc-200">
                    <option value="">{{ __('Semua lokasi') }}</option>
                    @foreach ($locations as $option)
                        <option value="{{ $option }}">{{ $option }}</option>
                    @endforeach
                </select>
            </label>

            <label class="flex flex-col gap-1">
                <span class="text-xs font-semibold text-zinc-500 dark:text-zinc-400">{{ __('Urutkan') }}</span>
                <select wire:model.live="sort" class="block h-11 w-full rounded-xl border border-zinc-200 bg-white px-3 text-base text-zinc-700 focus:outline-hidden focus:ring-2 focus:ring-accent sm:h-10 sm:text-sm dark:border-zinc-600 dark:bg-white/10 dark:text-zinc-200">
                    <option value="deadline">{{ __('Deadline terdekat') }}</option>
                    <option value="name">{{ __('Nama A-Z') }}</option>
                </select>
            </label>

            <label class="flex items-center gap-2 pb-2 sm:col-span-2">
                <input type="checkbox" wire:model.live="include_closed" class="size-4 rounded border-zinc-300 text-brand-teal focus:ring-brand-teal">
                <span class="text-sm text-zinc-600 dark:text-zinc-300">{{ __('Tampilkan yang sudah ditutup') }}</span>
            </label>
        </div>

        <div class="flex min-h-5 flex-wrap items-center justify-between gap-2">
            <x-loading.inline loading="scholarship.search" target="search,level,scope,location,sort,include_closed,resetFilters" />

            <button type="button" wire:click="resetFilters" wire:loading.attr="disabled" wire:target="resetFilters" class="ms-auto text-sm font-semibold text-brand-teal transition hover:underline disabled:opacity-50 dark:text-brand-teal-light">
                {{ __('Reset filter') }}
            </button>
        </div>
    </x-ui.card>

    @if ($scholarships->isEmpty())
        <x-ui.empty-state
            icon="face-frown"
            :title="__('Belum ada beasiswa yang cocok')"
            :description="__('Coba ubah kata kunci atau filter, atau kembali lagi saat periode pendaftaran berikutnya dibuka.')"
        >
            <x-actions.button type="button" variant="outline" icon="arrow-path" wire:click="resetFilters">
                {{ __('Reset filter') }}
            </x-actions.button>
        </x-ui.empty-state>
    @else
        <div
            class="grid gap-4 transition-opacity sm:grid-cols-2 lg:grid-cols-3"
            wire:loading.class="opacity-60"
            wire:target="search,level,scope,location,sort,include_closed,resetFilters"
            data-test="scholarship-grid"
        >
            @foreach ($scholarships as $scholarship)
                @php
                    $is_selected = in_array($scholarship->id, $selected_ids, true);
                    $is_open = $scholarship->isOpen();
                    $days = $scholarship->daysRemaining();
                    $urgent = $is_open && $days !== null && $days <= 7;
                @endphp

                <x-ui.card :variant="$is_selected ? 'mint' : 'plain'" hover class="flex h-full flex-col gap-4" data-test="scholarship-card">
                    <div class="flex items-start justify-between gap-3">
                        <x-ui.icon-badge icon="academic-cap" :tone="$is_selected ? 'mint' : 'teal'" solid />

                        <div class="flex flex-wrap justify-end gap-2">
                            @if ($is_selected)
                                <x-ui.pill variant="success" icon="check-badge">{{ __('Dipilih') }}</x-ui.pill>
                            @elseif (! $is_open)
                                <x-ui.pill variant="warning">{{ __('Ditutup') }}</x-ui.pill>
                            @elseif ($urgent)
                                <x-ui.pill variant="danger" icon="fire">{{ __(':days hari lagi', ['days' => $days]) }}</x-ui.pill>
                            @endif
                        </div>
                    </div>

                    <div class="flex-1">
                        <h3 class="text-base font-bold break-words text-zinc-900 dark:text-white">{{ $scholarship->scholarship_name }}</h3>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $scholarship->institution }}</p>

                        <div class="mt-3 flex flex-wrap gap-2">
                            <x-ui.pill>{{ $scholarship->level->label() }}</x-ui.pill>

                            @if ($scholarship->scope)
                                <x-ui.pill icon="globe-alt">{{ $scholarship->scope->label() }}</x-ui.pill>
                            @endif

                            @if ($scholarship->location)
                                <x-ui.pill icon="map-pin">{{ $scholarship->location }}</x-ui.pill>
                            @endif
                        </div>

                        @if ($scholarship->hasKeyBenefits())
                            <ul class="mt-3 flex flex-col gap-1.5">
                                @foreach (array_slice($scholarship->keyBenefits(), 0, 2) as $benefit)
                                    <li class="flex items-start gap-2 text-xs text-zinc-600 dark:text-zinc-300">
                                        <x-icons.app-icon name="gift" class="mt-0.5 size-3.5 shrink-0 text-brand-orange" />
                                        <span>{{ $benefit }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    <div class="flex items-center justify-between gap-2 border-t border-brand-teal/10 pt-3 dark:border-white/10">
                        <div class="text-xs">
                            <p class="font-semibold tracking-wide text-zinc-400 uppercase dark:text-zinc-500">{{ __('Tutup') }}</p>
                            <p class="font-bold text-zinc-700 dark:text-zinc-200">
                                {{ $scholarship->closing_date?->translatedFormat('d M Y') ?? __('Belum ditentukan') }}
                            </p>
                        </div>

                        <x-actions.link-button :href="route('scholarships.show', $scholarship)" wire:navigate size="sm" variant="outline" iconTrailing="arrow-right">
                            {{ __('Lihat Detail') }}
                        </x-actions.link-button>
                    </div>
                </x-ui.card>
            @endforeach
        </div>

        <div>
            {{ $scholarships->links() }}
        </div>
    @endif
</div>
