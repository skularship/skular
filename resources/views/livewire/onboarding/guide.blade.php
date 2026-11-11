{{--
    "Panduan" page (App\Livewire\Onboarding\Guide): the Skular journey from landing page to reporting results,
    one card per stage with "Buka halaman" and "Ulangi tur" (replays the product tour of that page),
    plus the product rules a new mentee should know.
--}}
<div class="mx-auto flex w-full max-w-4xl flex-col gap-6">
    <x-ui.section-heading
        :eyebrow="__('Panduan penggunaan')"
        eyebrow-icon="question-mark-circle"
        level="1"
        :title="__('Panduan Skular')"
        :description="__('Begini alur persiapan beasiswamu di Skular, dari awal sampai melaporkan hasil. Setiap tahap punya tur singkat yang bisa kamu ulang kapan saja.')"
    />

    <ol class="relative flex flex-col gap-4" data-test="guide-stages">
        @foreach ($stages as $stage)
            @php $tour = $stage['tour']; @endphp

            <li class="relative flex gap-4" wire:key="guide-{{ $tour->value }}">
                @if (! $loop->last)
                    <span class="absolute top-12 bottom-[-1rem] left-[1.375rem] w-0.5 rounded-full bg-brand-teal/15" aria-hidden="true"></span>
                @endif

                <span @class([
                    'relative z-10 flex size-11 shrink-0 items-center justify-center rounded-2xl text-sm font-extrabold shadow-soft',
                    'brand-gradient text-brand-ink' => $stage['seen'],
                    'bg-white text-brand-teal ring-1 ring-brand-teal/20' => ! $stage['seen'],
                ])>{{ $loop->iteration }}</span>

                <div class="flex min-w-0 flex-1 flex-col gap-3 rounded-3xl border border-brand-teal/10 bg-white p-5 shadow-soft sm:flex-row sm:items-center sm:justify-between" data-test="guide-stage">
                    <div class="flex min-w-0 items-start gap-3">
                        <x-icons.app-icon :name="$tour->icon()" class="mt-0.5 size-5 shrink-0 text-brand-teal" />
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-base font-bold text-zinc-900">{{ $tour->label() }}</h2>
                                @if ($stage['seen'])
                                    <x-ui.pill variant="success" icon="check">{{ __('Tur sudah dilihat') }}</x-ui.pill>
                                @endif
                            </div>
                            <p class="mt-1 text-sm text-zinc-600">{{ $tour->summary() }}</p>
                        </div>
                    </div>

                    <div class="flex shrink-0 flex-wrap gap-2">
                        @if ($tour === \App\Enums\ProductTour::Landing)
                            {{-- Full page load: the landing page is outside the app layout. --}}
                            <x-actions.link-button :href="route($tour->routeName())" variant="outline" size="sm">
                                {{ __('Buka halaman') }}
                            </x-actions.link-button>
                        @elseif ($tour !== \App\Enums\ProductTour::Module)
                            <x-actions.link-button :href="route($tour->routeName())" wire:navigate variant="outline" size="sm">
                                {{ __('Buka halaman') }}
                            </x-actions.link-button>
                        @endif

                        <x-actions.button type="button" size="sm" icon="play" wire:click="replay('{{ $tour->value }}')" data-test="replay-{{ $tour->value }}">
                            {{ __('Ulangi tur') }}
                        </x-actions.button>
                    </div>
                </div>
            </li>
        @endforeach
    </ol>

    <x-ui.card variant="amber" class="flex flex-col gap-4" data-test="guide-rules">
        <div class="flex items-center gap-3">
            <x-ui.icon-badge icon="light-bulb" tone="amber" />
            <h2 class="text-lg font-bold text-zinc-900">{{ __('Hal penting yang perlu kamu tahu') }}</h2>
        </div>

        <ul class="grid gap-3 sm:grid-cols-2">
            @foreach ([
                ['academic-cap', __('Kamu bisa menyiapkan maksimal 5 beasiswa sekaligus, dengan satu beasiswa utama.')],
                ['sparkles', __('Hasil latihan ditampilkan sebagai label (Sangat baik, Baik, Cukup, Perlu diperbaiki), bukan angka.')],
                ['pencil-square', __('AI membantu menjelaskan, menilai, dan memberi kerangka. Jawaban akhirmu tetap kamu tulis sendiri.')],
                ['chat-bubble-left-right', __('AI Mentor bisa menjawab maksimal :count pertanyaan per modul. Saran pertanyaan dari tim tidak memakai kuota.', ['count' => $mentor_limit])],
                ['lock-closed', __('Checkpoint terbuka setelah semua latihan tersimpan dan selesai dinilai.')],
                ['shield-check', __('Jawaban dan progres belajarmu tersimpan aman dan tidak hilang saat kamu pindah beasiswa.')],
            ] as [$icon, $text])
                <li class="flex items-start gap-2.5 rounded-2xl bg-white/80 px-4 py-3 text-sm text-zinc-700">
                    <x-icons.app-icon :name="$icon" class="mt-0.5 size-4 shrink-0 text-brand-orange" />
                    <span>{{ $text }}</span>
                </li>
            @endforeach
        </ul>
    </x-ui.card>

    <div class="flex flex-col gap-3 rounded-3xl border border-dashed border-brand-teal/25 bg-white/70 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-zinc-700">{{ __('Masih bingung atau menemukan error?') }}</p>
        <x-actions.link-button :href="route('support.index')" wire:navigate variant="outline" icon="lifebuoy">
            {{ __('Buka Bantuan') }}
        </x-actions.link-button>
    </div>
</div>
