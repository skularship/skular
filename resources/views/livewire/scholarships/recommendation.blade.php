{{--
    Recommendation page after profiling: a responsive grid of square (1:1) scholarship cards.
    Each card shows only the image, name, match label (never the numeric score), a select checkbox,
    a primary-choice star and a link to the detail page; deadlines, quota, location and the
    eligibility reasons live on the detail page. The 5-scholarship cap is enforced server-side.
--}}
<div class="mx-auto flex w-full max-w-6xl flex-col gap-6" x-data>
        <x-ui.section-heading
            :eyebrow="__('Scholarship matching')"
            eyebrow-icon="academic-cap"
            level="1"
            :title="__('Beasiswa yang cocok untukmu')"
            :description="__('Hasil ini dihitung dari profil, IPK, usia, SKTM, dan preferensi lokasimu. Pilih maksimal :count beasiswa, lalu tandai satu sebagai beasiswa utama.', ['count' => $max_selections])"
        >
            <x-actions.link-button :href="route('profiling')" wire:navigate variant="outline" icon="pencil-square">
                {{ __('Edit profil') }}
            </x-actions.link-button>
        </x-ui.section-heading>

        @error('selected')
            <x-feedback.callout variant="warning" :heading="__('Pilih beasiswa dulu')">
                {{ $message }}
            </x-feedback.callout>
        @enderror

        @if ($matches->isEmpty())
            <x-ui.empty-state
                icon="academic-cap"
                :title="__('Belum menemukan beasiswa yang cocok')"
                :description="__('Belum ada beasiswa aktif yang sesuai dengan jenjang dan preferensi lokasimu. Coba perbarui profilmu atau kembali lagi setelah periode pendaftaran berikutnya dibuka.')"
                :action-label="__('Perbarui profil')"
                :action-url="route('profiling')"
                action-icon="pencil-square"
                :secondary-action-label="__('Kembali ke dashboard')"
                :secondary-action-url="route('dashboard')"
                data-test="recommendation-empty"
            />
        @else
            {{-- Selection state lives in $wire.selected so highlights and the counter react instantly;
                 confirmSelection() re-validates everything on the server. --}}
            <ul
                class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 lg:grid-cols-4"
                x-data="{ max: {{ $max_selections }}, isSelected(id) { return ($wire.selected ?? []).map(Number).includes(id) }, isFull() { return ($wire.selected ?? []).length >= this.max } }"
                data-test="recommendation-grid"
            >
                @foreach ($matches as $match)
                    @php
                        $scholarship = $match->scholarship;
                        $is_primary = $primary === $scholarship->id;
                        $tier_variant = match ($match->tier->getColor()) {
                            'success' => 'success',
                            'warning' => 'warning',
                            default => 'info',
                        };
                        $urgent = $scholarship->is_open && $scholarship->days_remaining !== null && $scholarship->days_remaining <= 7;
                        $checkbox_id = 'pilih-beasiswa-'.$scholarship->id;
                    @endphp

                    <li wire:key="recommendation-{{ $scholarship->id }}">
                        <article
                            class="group relative flex aspect-square flex-col overflow-hidden rounded-3xl border border-brand-teal/10 bg-white shadow-soft transition focus-within:ring-2 focus-within:ring-brand-teal/40"
                            x-bind:class="isSelected({{ $scholarship->id }}) && 'border-brand-teal ring-2 ring-brand-teal'"
                            data-test="scholarship-card"
                        >
                            <div class="relative h-1/2 shrink-0">
                                <x-scholarship.card-image :url="$scholarship->image_url" :muted="! $scholarship->is_open" />

                                {{-- Select control: a real checkbox with a visible label, above the card link. --}}
                                <label
                                    for="{{ $checkbox_id }}"
                                    @class([
                                        'absolute top-2 left-2 z-20 inline-flex min-h-9 items-center gap-1.5 rounded-full bg-white/95 py-1.5 ps-2 pe-3 text-xs font-bold text-zinc-800 shadow-soft',
                                        'cursor-pointer' => $scholarship->is_open,
                                        'cursor-not-allowed text-zinc-500' => ! $scholarship->is_open,
                                    ])
                                >
                                    <input
                                        id="{{ $checkbox_id }}"
                                        type="checkbox"
                                        wire:model="selected"
                                        value="{{ $scholarship->id }}"
                                        @disabled(! $scholarship->is_open)
                                        x-bind:disabled="{{ $scholarship->is_open ? 'false' : 'true' }} || (isFull() && ! isSelected({{ $scholarship->id }}))"
                                        class="size-4 rounded border-zinc-300 text-brand-teal focus:ring-brand-teal disabled:cursor-not-allowed disabled:opacity-50"
                                        aria-label="{{ __('Pilih :name', ['name' => $scholarship->scholarship_name]) }}"
                                    >
                                    <span aria-hidden="true">
                                        @if ($scholarship->is_open)
                                            <span x-text="isSelected({{ $scholarship->id }}) ? '{{ __('Dipilih') }}' : '{{ __('Pilih') }}'">{{ __('Pilih') }}</span>
                                        @else
                                            {{ __('Ditutup') }}
                                        @endif
                                    </span>
                                </label>

                                @if ($scholarship->is_open)
                                    <button
                                        type="button"
                                        wire:click="makePrimary({{ $scholarship->id }})"
                                        @class([
                                            'absolute top-2 right-2 z-20 flex size-9 items-center justify-center rounded-full shadow-soft transition focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-teal',
                                            'bg-brand-amber text-brand-ink' => $is_primary,
                                            'bg-white/95 text-zinc-500 hover:text-brand-orange' => ! $is_primary,
                                        ])
                                        aria-pressed="{{ $is_primary ? 'true' : 'false' }}"
                                        aria-label="{{ $is_primary ? __(':name adalah beasiswa utama', ['name' => $scholarship->scholarship_name]) : __('Jadikan :name beasiswa utama', ['name' => $scholarship->scholarship_name]) }}"
                                        title="{{ $is_primary ? __('Beasiswa utama') : __('Jadikan utama') }}"
                                        data-test="make-primary"
                                    >
                                        <x-icons.app-icon name="star" :variant="$is_primary ? 's' : 'o'" class="size-4.5" />
                                    </button>
                                @endif

                                <x-ui.pill :variant="$tier_variant" class="absolute bottom-2 left-2 z-10 shadow-soft" data-test="match-tier">
                                    {{ $match->tier->label() }}
                                </x-ui.pill>
                            </div>

                            <div class="flex min-h-0 flex-1 flex-col gap-1 p-3 sm:p-4">
                                <h2 class="line-clamp-2 text-[0.8125rem] leading-snug font-bold break-words text-zinc-900 sm:text-sm">
                                    {{-- Stretched link: the whole card opens the detail page; the checkbox and star sit above it. --}}
                                    <a
                                        href="{{ route('scholarships.show', $scholarship->id) }}"
                                        wire:navigate
                                        class="after:absolute after:inset-0 after:z-0 focus-visible:outline-hidden"
                                    >
                                        {{ $scholarship->scholarship_name }}
                                        <span class="sr-only">{{ __('— lihat detail') }}</span>
                                    </a>
                                </h2>

                                <p class="mt-auto flex items-center gap-1 text-xs font-semibold" aria-hidden="true">
                                    @if (! $scholarship->is_open)
                                        <span class="text-red-600">{{ __('Pendaftaran ditutup') }}</span>
                                    @elseif ($urgent)
                                        <span class="inline-flex items-center gap-1 text-brand-orange">
                                            <x-icons.app-icon name="fire" class="size-3.5" />
                                            {{ __(':days hari lagi', ['days' => $scholarship->days_remaining]) }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-brand-teal group-hover:underline">
                                            {{ __('Lihat detail') }}
                                            <x-icons.app-icon name="arrow-right" class="size-3" />
                                        </span>
                                    @endif
                                </p>
                            </div>
                        </article>
                    </li>
                @endforeach
            </ul>

            {{-- Sit above the fixed mobile tab bar; `bottom-4` would hide the
                 save action behind it until the page is scrolled further. --}}
            <div class="sticky bottom-20 z-20 lg:bottom-4">
                <x-ui.card class="flex flex-col gap-4 shadow-lg sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold text-zinc-900" aria-live="polite">
                            <span x-text="($wire.selected ?? []).length">{{ count($selected) }}</span>
                            {{ __('dari :max beasiswa dipilih', ['max' => $max_selections]) }}
                        </p>
                        <p class="text-xs text-zinc-500">
                            {{ __('Tekan ikon bintang untuk memilih beasiswa utama. Beasiswa utama tampil di dashboard dan bisa kamu ubah kapan saja.') }}
                        </p>
                    </div>

                    <x-actions.button
                        type="button"
                        iconTrailing="arrow-right"
                        wire:click="confirmSelection"
                        loading="scholarship.save"
                        data-test="confirm-selection"
                    >
                        {{ __('Simpan & mulai belajar') }}
                    </x-actions.button>
                </x-ui.card>
            </div>
        @endif
</div>
