{{--
    Roadmap Belajar page (App\Livewire\Roadmap). Shows finished fases (collapsed), the active fase
    with its modules, the next fase by name only, and the remaining fases behind animated clouds.
    "Lihat lebih lanjut" reveals fase names only; locked fases arrive as FaseOutlineData without modules.
--}}
<div class="mx-auto flex w-full max-w-4xl flex-col gap-6">
    <x-ui.section-heading
        :eyebrow="__('Ruang belajar')"
        eyebrow-icon="map"
        level="1"
        :title="__('Roadmap Belajar')"
        :description="__('Fokus ke fase yang sedang kamu kerjakan. Fase berikutnya terbuka setelah fase ini selesai.')"
    >
        <x-actions.link-button :href="route('dashboard')" wire:navigate variant="outline" icon="home">
            {{ __('Ke dashboard') }}
        </x-actions.link-button>
    </x-ui.section-heading>

    @if (! $user->hasCompletedProfile())
        <x-ui.empty-state
            icon="identification"
            :title="__('Lengkapi profiling dulu ya')"
            :description="__('Roadmap disusun dari beasiswa yang kamu pilih setelah profiling.')"
            :action-label="__('Mulai profiling')"
            :action-url="route('profiling')"
            action-icon-trailing="arrow-right"
            data-test="roadmap-profile-empty"
        />
    @elseif ($roadmap->isEmpty())
        <x-ui.empty-state
            variant="activity"
            icon="map"
            :title="__('Roadmap belum tersedia')"
            :description="__('Pilih beasiswa atau tunggu tim Skular menambahkan modul pembelajaran.')"
            :action-label="$active_application ? null : __('Lihat rekomendasi')"
            :action-url="$active_application ? null : route('profiling.recommendation')"
            data-test="roadmap-empty"
        />
    @else
        {{-- Path context: which scholarship the roadmap follows, and what waits in the queue. --}}
        <div class="flex flex-col gap-3 rounded-3xl border border-brand-teal/12 bg-white p-5 shadow-soft sm:flex-row sm:items-center sm:justify-between" data-test="roadmap-context">
            <div class="flex min-w-0 items-start gap-3">
                <x-ui.icon-badge icon="academic-cap" tone="teal" />

                <div class="min-w-0">
                    @if ($active_application)
                        <p class="text-xs font-bold tracking-wide text-brand-teal uppercase">{{ __('Jalur aktif') }}</p>
                        <p class="text-base font-bold break-words text-zinc-900">{{ $active_application->scholarship?->scholarship_name }}</p>

                        @if ($queued_count > 0)
                            <p class="mt-0.5 text-xs text-zinc-500" data-test="roadmap-queue-note">
                                {{ __('+:count beasiswa lain di antreanmu. Jalurnya terbuka setelah jalur ini selesai, tanpa mengulang Modul Inti.', ['count' => $queued_count]) }}
                            </p>
                        @endif
                    @else
                        <p class="text-xs font-bold tracking-wide text-brand-orange uppercase">{{ __('Belum ada beasiswa aktif') }}</p>
                        <p class="text-sm text-zinc-700">
                            {{ __('Sementara roadmap ini berisi Modul Inti. Pilih beasiswa supaya modul khususnya ikut muncul.') }}
                        </p>
                    @endif
                </div>
            </div>

            <div class="flex shrink-0 items-center gap-3">
                <x-ui.pill variant="brand" icon="flag">{{ __(':percentage% selesai', ['percentage' => $summary->percentage]) }}</x-ui.pill>

                @unless ($active_application)
                    <x-actions.link-button :href="route('profiling.recommendation')" wire:navigate size="sm" iconTrailing="arrow-right">
                        {{ __('Pilih beasiswa') }}
                    </x-actions.link-button>
                @endunless
            </div>
        </div>

        @if ($active_application && $summary->template_total_modules === 0)
            <p class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs font-medium text-amber-800">
                {{ __('Belum ada modul Template untuk beasiswa ini. Kamu tetap bisa menyelesaikan Modul Inti; tim Skular sedang menyiapkan materi khusus beasiswanya.') }}
            </p>
        @endif

        {{-- Path finished. --}}
        @if ($roadmap->isFinished())
            <div class="flex flex-col gap-4 rounded-3xl border border-emerald-200 bg-brand-mint-soft/70 p-5 sm:flex-row sm:items-center sm:justify-between" data-test="roadmap-finished">
                <div class="flex items-start gap-3">
                    <x-ui.icon-badge icon="check-badge" tone="mint" solid />
                    <div>
                        <p class="text-base font-bold text-zinc-900">{{ __('Semua fase di jalur ini sudah selesai') }}</p>
                        <p class="mt-0.5 text-sm text-zinc-600">
                            @if ($path_completed && $next_application)
                                {{ __('Lanjutkan ke :name dari dashboard. Modul Inti yang sudah selesai tetap tersimpan.', ['name' => $next_application->scholarship?->scholarship_name ?? __('beasiswa berikutnya')]) }}
                            @else
                                {{ __('Hasil belajar dan feedback-mu tetap tersimpan. Kamu bisa membuka ulang modul kapan saja.') }}
                            @endif
                        </p>
                    </div>
                </div>

                @if ($path_completed && $next_application)
                    <x-actions.link-button :href="route('dashboard')" wire:navigate iconTrailing="arrow-right" class="shrink-0">
                        {{ __('Lanjut di dashboard') }}
                    </x-actions.link-button>
                @endif
            </div>
        @endif

        {{-- Finished fases: collapsed, still open for "Ulangi modul". --}}
        @if ($roadmap->completed !== [])
            <section class="flex flex-col gap-3" aria-labelledby="roadmap-completed-heading" data-test="roadmap-completed">
                <h2 id="roadmap-completed-heading" class="text-sm font-bold tracking-wide text-zinc-500 uppercase">{{ __('Fase selesai') }}</h2>

                @foreach ($roadmap->completed as $fase)
                    <details class="group rounded-3xl border border-emerald-200/80 bg-white shadow-soft" wire:key="roadmap-done-{{ $fase->id }}">
                        <summary class="flex min-h-14 cursor-pointer list-none items-center justify-between gap-3 rounded-3xl px-5 py-3 focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-teal [&::-webkit-details-marker]:hidden">
                            <span class="flex min-w-0 items-center gap-3">
                                <span class="flex size-8 shrink-0 items-center justify-center rounded-full brand-gradient text-brand-ink" aria-hidden="true">
                                    <x-icons.app-icon name="check" class="size-4" />
                                </span>
                                <span class="min-w-0">
                                    <span class="block text-sm font-bold break-words text-zinc-900">{{ $fase->title }}</span>
                                    <span class="block text-xs text-zinc-500">{{ __(':total modul selesai', ['total' => $fase->total_modules]) }}</span>
                                </span>
                            </span>
                            <x-icons.app-icon name="chevron-down" class="size-4 text-zinc-400 transition group-open:rotate-180" />
                        </summary>

                        <div class="border-t border-emerald-100 px-5 py-5">
                            @include('livewire.roadmap.partials.fase-modules', ['fase' => $fase])
                        </div>
                    </details>
                @endforeach
            </section>
        @endif

        {{-- Active fase: the only unfinished fase whose modules are shown. --}}
        @if ($roadmap->active)
            @php $fase = $roadmap->active; @endphp

            <x-ui.card padding="none" class="overflow-hidden ring-2 ring-brand-teal/20" data-test="roadmap-active-fase">
                <div class="flex flex-wrap items-center justify-between gap-3 bg-linear-to-r from-brand-teal/10 to-brand-amber/10 px-5 py-4">
                    <div class="min-w-0">
                        <x-ui.pill variant="info" icon="play">{{ __('Fase aktif') }}</x-ui.pill>
                        <h2 class="mt-2 text-lg font-bold break-words text-zinc-900">{{ $fase->title }}</h2>
                        @if (filled($fase->subtitle))
                            <p class="text-sm text-zinc-600">{{ $fase->subtitle }}</p>
                        @endif
                    </div>

                    <div class="flex min-w-[9rem] flex-col gap-1.5">
                        <span class="text-xs font-semibold text-zinc-600">
                            {{ __(':done/:total modul', ['done' => $fase->completed_modules, 'total' => $fase->total_modules]) }}
                        </span>
                        <x-ui.progress :value="$fase->percentage" :showValue="false" size="sm" />
                    </div>
                </div>

                <div class="p-5">
                    @include('livewire.roadmap.partials.fase-modules', ['fase' => $fase])
                </div>
            </x-ui.card>
        @endif

        {{-- Next fase: name only. --}}
        @if ($roadmap->upcoming)
            <div class="flex items-center gap-4 rounded-3xl border border-dashed border-brand-teal/30 bg-white/70 px-5 py-4" data-test="roadmap-upcoming-fase">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-2xl bg-brand-teal-soft text-brand-teal" aria-hidden="true">
                    <x-icons.app-icon name="lock-closed" class="size-5" />
                </span>

                <div class="min-w-0">
                    <p class="text-xs font-bold tracking-wide text-zinc-500 uppercase">{{ __('Fase berikutnya') }}</p>
                    <p class="text-base font-bold break-words text-zinc-900">{{ $roadmap->upcoming->title }}</p>
                    @if (filled($roadmap->upcoming->subtitle))
                        <p class="text-sm text-zinc-600">{{ $roadmap->upcoming->subtitle }}</p>
                    @endif
                    @if ($roadmap->active)
                        <p class="mt-1 text-xs text-zinc-500">{{ __('Terbuka setelah :fase selesai.', ['fase' => $roadmap->active->title]) }}</p>
                    @endif
                </div>
            </div>
        @endif

        {{-- Fases further ahead: hidden behind drifting clouds; only their names can be revealed. --}}
        @if ($roadmap->locked !== [])
            <section class="sk-cloud-veil relative overflow-hidden rounded-3xl border border-brand-teal/10 bg-linear-to-b from-brand-teal-soft/70 via-brand-cream to-white px-5 py-8 shadow-soft" aria-labelledby="roadmap-locked-heading" data-test="roadmap-locked">
                @foreach ([['sk-cloud--1', 'top-3 -left-6 w-36'], ['sk-cloud--2', 'top-10 right-0 w-44'], ['sk-cloud--3', 'bottom-2 left-1/3 w-32']] as [$cloud_class, $cloud_position])
                    <svg class="sk-cloud {{ $cloud_class }} {{ $cloud_position }}" viewBox="0 0 64 36" fill="currentColor" aria-hidden="true" focusable="false">
                        <path d="M16 34h34a12 12 0 0 0 1.6-23.9A16 16 0 0 0 21.3 7.8 11 11 0 0 0 16 34Z" />
                    </svg>
                @endforeach

                <div class="relative z-10 mx-auto flex max-w-md flex-col items-center gap-3 text-center">
                    <span class="flex size-12 items-center justify-center rounded-2xl bg-white text-brand-teal shadow-soft" aria-hidden="true">
                        <x-icons.app-icon name="lock-closed" class="size-6" />
                    </span>

                    <h2 id="roadmap-locked-heading" class="text-base font-bold text-zinc-900">
                        {{ __(':count fase lagi masih tertutup awan', ['count' => count($roadmap->locked)]) }}
                    </h2>
                    <p class="text-sm text-zinc-600">
                        {{ __('Fokus selesaikan fase aktifmu dulu. Isi fase-fase ini terbuka satu per satu.') }}
                    </p>

                    @if ($show_locked_fases)
                        <ol class="mt-2 flex w-full flex-col gap-2 text-left" data-test="roadmap-locked-names">
                            @foreach ($roadmap->locked as $outline)
                                <li class="flex items-center gap-3 rounded-2xl bg-white/90 px-4 py-3 shadow-soft" wire:key="roadmap-locked-{{ $outline->id }}">
                                    <x-icons.app-icon name="lock-closed" class="size-4 text-zinc-400" />
                                    <span class="min-w-0">
                                        <span class="block text-sm font-semibold break-words text-zinc-800">{{ $outline->title }}</span>
                                        @if (filled($outline->subtitle))
                                            <span class="block text-xs text-zinc-500">{{ $outline->subtitle }}</span>
                                        @endif
                                    </span>
                                </li>
                            @endforeach
                        </ol>

                        <button type="button" wire:click="hideLockedFases" class="inline-flex min-h-11 items-center gap-1.5 rounded-xl px-4 text-sm font-semibold text-brand-teal transition hover:underline focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-teal">
                            {{ __('Sembunyikan') }}
                            <x-icons.app-icon name="chevron-up" class="size-4" />
                        </button>
                    @else
                        <x-actions.button type="button" variant="outline" iconTrailing="chevron-down" wire:click="showLockedFases" data-test="roadmap-show-more">
                            {{ __('Lihat lebih lanjut') }}
                        </x-actions.button>
                    @endif
                </div>
            </section>
        @endif
    @endif
</div>
