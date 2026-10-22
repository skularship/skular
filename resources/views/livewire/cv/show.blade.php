<div class="mx-auto flex w-full max-w-4xl flex-col gap-6 print:gap-4">
    <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between print:hidden">
        <div>
            <x-ui.pill variant="brand" icon="document-text">{{ __('CV & Data Diri') }}</x-ui.pill>
            <h1 class="mt-3 text-2xl font-extrabold tracking-tight text-zinc-900 sm:text-3xl dark:text-white">{{ __('CV Saya') }}</h1>
            <p class="mt-1 max-w-2xl text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Data di halaman ini terisi otomatis dari jawaban latihan praktik yang ditandai admin sebagai bagian CV.') }}
            </p>
        </div>

        <div class="flex flex-wrap gap-3">
            <x-actions.button type="button" variant="outline" icon="printer" x-on:click="window.print()">
                {{ __('Cetak / simpan PDF') }}
            </x-actions.button>
        </div>
    </header>

    <section class="relative overflow-hidden rounded-4xl bg-linear-to-br from-brand-teal to-brand-teal-deep p-6 text-white shadow-soft print:bg-white print:text-zinc-900 print:shadow-none">
        <div class="pointer-events-none absolute -top-16 -right-10 size-48 rounded-full bg-brand-amber/25 blur-3xl print:hidden" aria-hidden="true"></div>

        <div class="relative z-10 flex flex-col gap-5">
            <div class="flex items-center gap-4">
                <span class="flex size-14 shrink-0 items-center justify-center rounded-3xl brand-gradient text-xl font-extrabold text-brand-ink shadow-pop sm:size-16">
                    {{ \Illuminate\Support\Str::initials($profile->name, true) }}
                </span>

                <div class="min-w-0">
                    <p class="break-words text-xl font-extrabold sm:text-2xl">{{ $profile->name }}</p>
                    <p class="break-all text-sm text-white/80 print:text-zinc-600">{{ $profile->email }}</p>
                </div>
            </div>

            <dl class="grid gap-3 border-t border-white/15 pt-5 sm:grid-cols-2 lg:grid-cols-3 print:border-zinc-200">
                @foreach ([
                    __('Nomor telepon') => $profile->phone,
                    __('Jenjang pendidikan') => $profile->level?->label(),
                    __('Usia') => $profile->age ? __(':age tahun', ['age' => $profile->age]) : null,
                    __('IPK') => $profile->gpa !== null ? number_format((float) $profile->gpa, 2) : null,
                    __('Jenjang target') => $profile->desired_level?->label(),
                    __('Lokasi kuliah') => $profile->desired_location?->label(),
                    __('Bidang studi') => $profile->cluster?->label,
                ] as $label => $value)
                    @if (filled($value))
                        <div class="rounded-2xl bg-white/10 px-4 py-3 backdrop-blur-sm print:bg-transparent print:px-0 print:py-0">
                            <dt class="text-[0.6875rem] font-semibold tracking-wide text-white/70 uppercase print:text-zinc-500">{{ $label }}</dt>
                            <dd class="mt-0.5 text-sm font-bold print:text-zinc-900">{{ $value }}</dd>
                        </div>
                    @endif
                @endforeach
            </dl>
        </div>
    </section>

    @if ($sections->isEmpty())
        <x-ui.empty-state
            icon="document-text"
            :title="__('Belum ada isi CV')"
            :description="__('Kerjakan latihan praktik yang ditandai admin — misalnya pengalaman organisasi dan prestasi — lalu jawabanmu otomatis muncul di sini.')"
        >
            <x-actions.link-button :href="route('dashboard')" wire:navigate iconTrailing="arrow-right">
                {{ __('Kembali ke roadmap') }}
            </x-actions.link-button>
        </x-ui.empty-state>
    @else
        @foreach ($sections as $section)
            <x-ui.card class="flex flex-col gap-4 print:border-zinc-300 print:shadow-none" data-test="cv-section">
                <div class="flex flex-wrap items-center gap-3 border-b border-brand-teal/10 pb-3 dark:border-white/10">
                    <x-ui.icon-badge icon="briefcase" tone="teal" size="sm" />

                    <h2 class="text-base font-bold text-zinc-900 dark:text-white">{{ $section->title }}</h2>

                    @if (filled($section->module_title))
                        <x-ui.pill>{{ $section->module_title }}</x-ui.pill>
                    @endif
                </div>

                <dl class="flex flex-col gap-4">
                    @foreach ($section->entries as $entry)
                        <div class="flex flex-col gap-0.5 sm:flex-row sm:items-start sm:gap-4">
                            <dt class="w-full shrink-0 text-xs font-bold tracking-wide text-brand-teal uppercase sm:w-52 dark:text-brand-teal-light">
                                {{ $entry['label'] }}
                            </dt>
                            <dd class="min-w-0 flex-1 whitespace-pre-line text-sm text-zinc-700 dark:text-zinc-200">
                                {{ $entry['value'] }}
                            </dd>
                        </div>
                    @endforeach
                </dl>
            </x-ui.card>
        @endforeach

        <p class="text-center text-xs text-zinc-400 print:hidden">
            {{ __('Dibuat dengan Skular — platform pembelajaran beasiswa berbasis AI.') }}
        </p>
    @endif
</div>
