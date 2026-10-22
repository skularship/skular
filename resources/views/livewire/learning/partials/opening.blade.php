@php
    $mission_tiles = [
        ['icon' => 'flag', 'tone' => 'peach', 'title' => __('Tujuan modul'), 'body' => filled($module->goal) ? $module->goal : __('Tujuan modul ini belum diatur oleh admin.')],
        ['icon' => 'clock', 'tone' => 'teal', 'title' => __('Estimasi waktu'), 'body' => $module->duration ? __('Sekitar :minutes menit, bisa kamu kerjakan sambil santai.', ['minutes' => $module->duration]) : __('Sesuaikan dengan ritmemu sendiri.')],
        ['icon' => 'book-open-text', 'tone' => 'mint', 'title' => __('Apa yang akan dipelajari'), 'body' => filled($module->summary_material) ? \Illuminate\Support\Str::limit(strip_tags($module->summary_material), 180) : __('Ringkasan materi sedang disiapkan.')],
    ];
@endphp

<div class="sk-rise flex flex-col gap-6">
    <div class="relative overflow-hidden rounded-4xl brand-gradient-warm p-6 text-brand-ink shadow-pop sm:p-8">
        <div class="pointer-events-none absolute -top-16 -right-10 size-48 rounded-full bg-white/30 blur-3xl" aria-hidden="true"></div>

        <div class="relative z-10 flex flex-col gap-3">
            <span class="inline-flex w-fit items-center gap-1.5 rounded-full bg-brand-ink/10 px-3 py-1 text-xs font-bold tracking-wide text-brand-ink uppercase">
                <x-icons.app-icon name="play" class="size-3.5" />
                {{ __('Pembukaan modul') }}
            </span>

            <h2 class="text-2xl font-extrabold tracking-tight sm:text-3xl">{{ __('Kenali dulu ya') }}</h2>

            <p class="max-w-2xl text-sm leading-relaxed text-brand-ink/80">
                {{ __('Modul ini mengikuti alur belajar Skular: kamu memahami materi, mempraktikkannya, menerima feedback AI, lalu mengunci progresmu lewat checkpoint.') }}
            </p>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        @foreach ($mission_tiles as $tile)
            <x-ui.card class="flex items-start gap-4">
                <x-ui.icon-badge :icon="$tile['icon']" :tone="$tile['tone']" />
                <div class="min-w-0">
                    <p class="text-sm font-bold text-zinc-900 dark:text-white">{{ $tile['title'] }}</p>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ $tile['body'] }}</p>
                </div>
            </x-ui.card>
        @endforeach

        <x-ui.card class="flex items-start gap-4">
            <x-ui.icon-badge icon="clipboard-document-check" tone="amber" />
            <div class="min-w-0">
                <p class="text-sm font-bold text-zinc-900 dark:text-white">{{ __('Output yang harus dibuat') }}</p>

                @if ($forms->isNotEmpty())
                    <ul class="mt-2 flex flex-col gap-1.5 text-sm text-zinc-600 dark:text-zinc-300">
                        @foreach ($forms as $form)
                            <li class="inline-flex items-start gap-2">
                                <x-icons.app-icon name="check-circle" class="mt-0.5 size-4 shrink-0 text-emerald-500" />
                                <span>{{ $form->title }}</span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Tidak ada latihan tertulis untuk modul ini.') }}</p>
                @endif
            </div>
        </x-ui.card>
    </div>

    <x-ui.card variant="teal" class="flex flex-col gap-3">
        <p class="flex items-center gap-2 text-sm font-bold text-zinc-900 dark:text-white">
            <x-icons.app-icon name="map" class="size-4 text-brand-teal dark:text-brand-teal-light" />
            {{ __('Cara menggunakan modul') }}
        </p>

        <ol class="grid gap-3 sm:grid-cols-2">
            @foreach ([
                __('Baca materi di bagian Pahami sampai kamu paham intinya.'),
                __('Jawab quick question singkat untuk menguji pemahamanmu.'),
                __('Kerjakan latihan praktik, lalu baca feedback dari AI.'),
                __('Setujui checkpoint untuk membuka modul berikutnya.'),
            ] as $index => $step)
                <li class="flex items-start gap-3 rounded-2xl bg-white/70 px-3 py-2.5 text-sm text-zinc-700 dark:bg-zinc-900/50 dark:text-zinc-200">
                    <span class="flex size-6 shrink-0 items-center justify-center rounded-full brand-gradient text-[0.6875rem] font-bold text-brand-ink">{{ $index + 1 }}</span>
                    <span>{{ $step }}</span>
                </li>
            @endforeach
        </ol>
    </x-ui.card>

    <div class="flex flex-wrap items-center gap-3 border-t border-brand-teal/10 pt-5 dark:border-white/10">
        <x-actions.button
            type="button"
            size="lg"
            iconTrailing="arrow-right"
            wire:click="start"
            loading="module.open"
            data-test="start-module"
        >
            {{ __('Mulai Modul') }}
        </x-actions.button>

        <x-actions.link-button :href="route('dashboard')" wire:navigate variant="outline">
            {{ __('Kembali ke dashboard') }}
        </x-actions.link-button>
    </div>
</div>
