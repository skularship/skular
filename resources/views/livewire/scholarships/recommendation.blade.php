<div class="mx-auto flex w-full max-w-6xl flex-col gap-6">
        <x-ui.section-heading
            :eyebrow="__('Scholarship matching')"
            eyebrow-icon="academic-cap"
            level="1"
            :title="__('Beasiswa yang cocok untukmu')"
            :description="__('Hasil ini dihitung dari profil, IPK, usia, SKTM, dan preferensi lokasimu.')"
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
                icon="face-frown"
                :title="__('Belum menemukan beasiswa yang cocok')"
                :description="__('Belum ada beasiswa aktif yang sesuai dengan jenjang dan preferensi lokasimu. Coba perbarui profilmu atau kembali lagi setelah periode pendaftaran berikutnya dibuka.')"
            >
                <div class="flex flex-wrap justify-center gap-3">
                    <x-actions.link-button :href="route('profiling')" wire:navigate icon="pencil-square">
                        {{ __('Perbarui profil') }}
                    </x-actions.link-button>
                    <x-actions.link-button :href="route('dashboard')" wire:navigate variant="outline">
                        {{ __('Kembali ke dashboard') }}
                    </x-actions.link-button>
                </div>
            </x-ui.empty-state>
        @else
            <div class="grid gap-4">
                @foreach ($matches as $match)
                    @php
                        $scholarship = $match->scholarship;
                        $is_selected = in_array($scholarship->id, $selected, true);
                        $tier_variant = match ($match->tier->getColor()) {
                            'success' => 'success',
                            'warning' => 'warning',
                            default => 'brand',
                        };
                    @endphp

                    <x-ui.card class="flex flex-col gap-4">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="flex items-start gap-3">
                                <label @class([
                                    'mt-1 flex items-center',
                                    'cursor-pointer' => $scholarship->is_open,
                                    'cursor-not-allowed' => ! $scholarship->is_open,
                                ])>
                                    <input
                                        type="checkbox"
                                        wire:model="selected"
                                        value="{{ $scholarship->id }}"
                                        @disabled(! $scholarship->is_open)
                                        class="size-5 rounded border-zinc-300 text-brand-teal focus:ring-brand-teal disabled:cursor-not-allowed disabled:opacity-50"
                                        aria-label="{{ __('Pilih :name', ['name' => $scholarship->scholarship_name]) }}"
                                    >
                                </label>

                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <x-ui.pill :variant="$tier_variant" icon="sparkles">
                                            {{ $match->tier->label() }} · {{ $match->score }}
                                        </x-ui.pill>

                                        <x-ui.pill>{{ $scholarship->status->label() }}</x-ui.pill>
                                    </div>

                                    <p class="mt-2 text-lg font-semibold break-words text-zinc-900 dark:text-white">
                                        {{ $scholarship->scholarship_name }}
                                    </p>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                        {{ $scholarship->institution }}
                                    </p>

                                    <div class="mt-3 flex flex-wrap gap-2">
                                        <x-ui.pill icon="academic-cap">{{ $scholarship->level->label() }}</x-ui.pill>

                                        @if ($scholarship->scope)
                                            <x-ui.pill icon="globe-alt">{{ $scholarship->scope->label() }}</x-ui.pill>
                                        @endif

                                        @if ($scholarship->location)
                                            <x-ui.pill icon="map-pin">{{ $scholarship->location }}</x-ui.pill>
                                        @endif

                                        @if ($scholarship->estimated_quota)
                                            <x-ui.pill icon="users">{{ __('Kuota :quota', ['quota' => $scholarship->estimated_quota]) }}</x-ui.pill>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="flex shrink-0 flex-col gap-3 sm:items-end">
                                <div class="text-left sm:text-right">
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Deadline') }}</p>
                                    <p class="text-sm font-semibold text-zinc-900 dark:text-white">
                                        {{ $scholarship->closing_date
                                            ? \Illuminate\Support\Carbon::parse($scholarship->closing_date)->translatedFormat('d M Y')
                                            : __('Tidak ada') }}
                                    </p>

                                    @if ($scholarship->days_remaining !== null && $scholarship->is_open)
                                        <x-ui.pill
                                            :variant="$scholarship->days_remaining <= 7 ? 'warning' : 'success'"
                                            class="mt-1"
                                        >
                                            {{ __(':days hari lagi', ['days' => $scholarship->days_remaining]) }}
                                        </x-ui.pill>
                                    @elseif (! $scholarship->is_open)
                                        <x-ui.pill variant="danger" class="mt-1">{{ __('Pendaftaran ditutup') }}</x-ui.pill>
                                    @endif
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    @if ($primary === $scholarship->id)
                                        <x-ui.pill variant="success" icon="star">{{ __('Beasiswa utama') }}</x-ui.pill>
                                    @else
                                        <button
                                            type="button"
                                            wire:click="makePrimary({{ $scholarship->id }})"
                                            class="inline-flex items-center gap-1.5 rounded-xl border border-zinc-200 px-3 py-1.5 text-xs font-medium text-zinc-600 transition hover:border-brand-teal hover:text-brand-teal dark:border-zinc-700 dark:text-zinc-300"
                                        >
                                            <x-icons.app-icon name="star" class="size-3.5" />
                                            {{ __('Jadikan utama') }}
                                        </button>
                                    @endif

                                    @if ($scholarship->link)
                                        <a
                                            href="{{ $scholarship->link }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="inline-flex items-center gap-1.5 rounded-xl border border-zinc-200 px-3 py-1.5 text-xs font-medium text-zinc-600 transition hover:border-brand-teal hover:text-brand-teal dark:border-zinc-700 dark:text-zinc-300"
                                        >
                                            <x-icons.app-icon name="arrow-up-right" class="size-3.5" />
                                            {{ __('Situs resmi') }}
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-2 rounded-xl bg-zinc-50/80 p-4 dark:bg-zinc-900/40 sm:grid-cols-2">
                            @foreach ($match->reasons as $reason)
                                <div class="flex items-start gap-2 text-sm">
                                    <x-icons.app-icon
                                        :name="$reason->satisfied ? 'check-circle' : 'exclamation-triangle'"
                                        class="mt-0.5 size-4 shrink-0 {{ $reason->satisfied ? 'text-emerald-500' : 'text-amber-500' }}"
                                    />

                                    <div>
                                        <span class="text-zinc-500 dark:text-zinc-400">{{ $reason->label }}:</span>
                                        <span class="text-zinc-700 dark:text-zinc-200">{{ $reason->detail }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </x-ui.card>
                @endforeach
            </div>

            {{-- Sit above the fixed mobile tab bar; `bottom-4` would hide the
                 save action behind it until the page is scrolled further. --}}
            <div class="sticky bottom-20 z-20 lg:bottom-4">
                <x-ui.card class="flex flex-col gap-4 shadow-lg sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold text-zinc-900 dark:text-white">
                            {{ trans_choice(':count beasiswa dipilih|:count beasiswa dipilih', count($selected)) }}
                        </p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                            {{ __('Beasiswa utama akan tampil di dashboard. Kamu bisa mengubahnya kapan saja.') }}
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
