{{-- Mentee dashboard (App\Livewire\Dashboard): progress summary, AI result label (never the numeric score) and a compact roadmap card linking to /roadmap. --}}
@php
    $first_name = \Illuminate\Support\Str::before($user->name, ' ');

    $quick_links = [
        ['label' => __('Semua Beasiswa'), 'url' => route('scholarships.index'), 'icon' => 'magnifying-glass', 'tone' => 'teal'],
        ['label' => __('Beasiswa Saya'), 'url' => route('scholarships.tracker'), 'icon' => 'document-arrow-up', 'tone' => 'peach'],
        ['label' => __('Perpustakaan'), 'url' => route('library.index'), 'icon' => 'book-open', 'tone' => 'mint'],
        ['label' => __('CV Saya'), 'url' => route('cv.show'), 'icon' => 'document-text', 'tone' => 'amber'],
    ];
@endphp

<div class="mx-auto flex w-full max-w-6xl flex-col gap-6">
    {{-- A. Welcome hero --}}
    <header class="sk-rise hero-mesh relative overflow-hidden rounded-4xl border border-brand-teal/12 p-6 shadow-soft sm:p-8">
        <div class="pointer-events-none absolute -top-16 -right-10 size-56 rounded-full bg-brand-amber/30 blur-3xl" aria-hidden="true"></div>

        <div class="relative z-10 flex flex-col gap-7 lg:flex-row lg:items-center lg:justify-between">
            <div class="max-w-xl">
                <x-ui.pill variant="brand" icon="sparkles">{{ __('Ruang belajar') }}</x-ui.pill>

                <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-zinc-900 sm:text-4xl dark:text-white">
                    {{ __('Hai, :name 👋', ['name' => $first_name]) }}
                </h1>

                <p class="mt-2 text-sm text-zinc-700 sm:text-base dark:text-zinc-200">
                    @if ($path_completed && $next_application)
                        {{ __('Jalur :done selesai. Fondasi Inti-mu tetap tersimpan, sekarang lanjutkan ke :next.', ['done' => $active_application->scholarship?->scholarship_name ?? __('beasiswa ini'), 'next' => $next_application->scholarship?->scholarship_name ?? __('beasiswa berikutnya')]) }}
                    @elseif ($path_completed || ($completed_paths->isNotEmpty() && ! $active_application))
                        {{ __('Semua jalur beasiswa yang kamu pilih sudah selesai. Fondasi belajarmu tetap tersimpan.') }}
                    @elseif ($active_application)
                        {{ __('Kamu sedang menyiapkan :name.', ['name' => $active_application->scholarship?->scholarship_name ?? __('sebuah beasiswa')]) }}
                    @else
                        {{ __('Yuk mulai perjalanan persiapan beasiswamu hari ini.') }}
                    @endif
                </p>

                <div class="mt-5 flex flex-wrap gap-3">
                    @if ($next_action['url'])
                        <x-actions.link-button :href="$next_action['url']" wire:navigate iconTrailing="arrow-right">
                            {{ $next_action['label'] }}
                        </x-actions.link-button>
                    @endif

                    <x-actions.link-button :href="route('scholarships.index')" wire:navigate variant="outline" icon="magnifying-glass">
                        {{ __('Jelajahi beasiswa') }}
                    </x-actions.link-button>
                </div>
            </div>

            <div class="flex flex-col items-start gap-4 rounded-3xl border border-white/60 bg-white/70 p-4 shadow-soft backdrop-blur-sm sm:flex-row sm:items-center sm:gap-5 dark:border-white/10 dark:bg-zinc-900/60">
                <x-ui.ring-progress :value="$summary->percentage" :size="112" :caption="__('Belajar')" />

                <div class="min-w-0">
                    <p class="text-xs font-semibold tracking-wide text-zinc-500 uppercase dark:text-zinc-400">{{ __('Progress belajar') }}</p>
                    <p class="mt-0.5 text-lg font-bold text-zinc-900 dark:text-white">
                        {{ __(':done / :total modul', ['done' => $summary->completed_modules, 'total' => $summary->total_modules]) }}
                    </p>

                    @if ($summary->current_module_title)
                        <p class="mt-1 text-xs leading-snug text-zinc-500 dark:text-zinc-400">
                            {{ __('Sedang di: :module', ['module' => $summary->current_module_title]) }}
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </header>

    @if (! $user->hasCompletedProfile())
        <x-ui.empty-state
            icon="identification"
            :title="__('Lengkapi profiling dulu ya')"
            :description="__('Tanpa profil yang lengkap, Skular belum bisa mencocokkanmu dengan beasiswa yang tepat.')"
            :action-label="__('Mulai profiling')"
            :action-url="route('profiling')"
            action-icon-trailing="arrow-right"
            data-test="profile-empty"
        />
    @endif

    {{-- B. Progress overview --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" data-test="progress-overview">
        <x-ui.stat
            icon="chart-bar"
            tone="amber"
            :label="__('Progress belajar')"
            :value="$summary->percentage.'%'"
            :hint="__(':done dari :total modul selesai', ['done' => $summary->completed_modules, 'total' => $summary->total_modules])"
        />

        <x-ui.stat
            icon="check-badge"
            tone="mint"
            :label="__('Modul selesai')"
            :value="$summary->completed_modules"
            :hint="__('Inti :core/:core_total · Template :template/:template_total', [
                'core' => $summary->core_completed_modules,
                'core_total' => $summary->core_total_modules,
                'template' => $summary->template_completed_modules,
                'template_total' => $summary->template_total_modules,
            ])"
        />

        <x-ui.stat
            icon="sparkles"
            tone="teal"
            :label="__('Hasil penilaian AI')"
            :value="$summary->average_score > 0 ? \App\Enums\AiScoreLabel::fromScore($summary->average_score)->label() : '—'"
            :hint="__('Rata-rata dari feedback AI')"
        />

        <x-ui.stat
            icon="academic-cap"
            tone="peach"
            :label="__('Beasiswa dipilih')"
            :value="__(':count/:max', ['count' => $selected_count, 'max' => $max_selections])"
            :hint="$active_application?->scholarship?->scholarship_name ?? __('Belum ada')"
        />
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="flex flex-col gap-6 lg:col-span-2">
            {{-- C. Current scholarship spotlight --}}
            <section class="flex flex-col gap-4">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-lg font-bold tracking-tight text-zinc-900 dark:text-white">{{ __('Beasiswa yang sedang disiapkan') }}</h2>
                    <a href="{{ route('scholarships.tracker') }}" wire:navigate class="text-sm font-semibold text-brand-teal transition hover:underline dark:text-brand-teal-light">
                        {{ __('Kelola semua') }}
                    </a>
                </div>

                @if ($active_application)
                    @php
                        $scholarship = $active_application->scholarship;
                        $days = $scholarship?->daysRemaining();
                        $urgent = $days !== null && $days <= 14;
                    @endphp

                    <div class="spotlight relative overflow-hidden rounded-4xl bg-linear-to-br from-brand-teal to-brand-teal-deep p-6 text-white shadow-soft sm:p-7" data-test="active-scholarship">
                        <div class="pointer-events-none absolute -top-20 -right-16 size-56 rounded-full bg-brand-amber/25 blur-3xl" aria-hidden="true"></div>
                        <div class="pointer-events-none absolute -bottom-24 -left-10 size-48 rounded-full bg-brand-teal-light/30 blur-3xl" aria-hidden="true"></div>

                        <div class="relative z-10 flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0">
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-2.5 py-1 text-xs font-semibold text-white backdrop-blur-sm">
                                    <x-icons.app-icon name="star" class="size-3.5 text-brand-amber" />
                                    {{ __('Beasiswa aktif') }}
                                </span>

                                <p class="mt-3 text-xl font-bold sm:text-2xl">{{ $scholarship?->scholarship_name }}</p>
                                <p class="text-sm text-white/80">{{ $scholarship?->institution }}</p>

                                <div class="mt-4 flex flex-wrap items-center gap-2">
                                    <span @class([
                                        'inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold',
                                        'bg-red-400/90 text-red-950' => $urgent,
                                        'bg-white/15 text-white' => ! $urgent,
                                    ])>
                                        <x-icons.app-icon name="calendar-days" class="size-3.5" />
                                        {{ $scholarship?->closing_date?->translatedFormat('d M Y') ?? __('Belum ditentukan') }}
                                    </span>

                                    @if ($days !== null)
                                        <span @class([
                                            'inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold',
                                            'bg-red-400/90 text-red-950' => $urgent,
                                            'bg-brand-amber text-brand-ink' => ! $urgent,
                                        ])>
                                            {{ trans_choice(':count hari lagi|:count hari lagi', $days, ['count' => $days]) }}
                                        </span>
                                    @endif

                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-xs font-semibold text-white">
                                        <x-icons.app-icon name="document-check" class="size-3.5" />
                                        {{ $active_application->status->label() }}
                                    </span>
                                </div>
                            </div>

                            <div class="shrink-0">
                                <x-ui.ring-progress :value="$summary->percentage" :size="120" :caption="__('Siap')" tone="mint" />
                            </div>
                        </div>

                        <div class="relative z-10 mt-6 grid gap-3 sm:grid-cols-3">
                            <div class="rounded-2xl bg-white/10 px-4 py-3 backdrop-blur-sm">
                                <p class="text-[0.6875rem] font-semibold tracking-wide text-white/70 uppercase">{{ __('Status') }}</p>
                                <p class="mt-0.5 text-sm font-bold text-white">{{ $active_application->status->label() }}</p>
                            </div>
                            <div class="rounded-2xl bg-white/10 px-4 py-3 backdrop-blur-sm">
                                <p class="text-[0.6875rem] font-semibold tracking-wide text-white/70 uppercase">{{ __('Progress belajar') }}</p>
                                <p class="mt-0.5 text-sm font-bold text-white">
                                    {{ __(':done dari :total modul', ['done' => $summary->completed_modules, 'total' => $summary->total_modules]) }}
                                </p>
                            </div>
                            <div class="rounded-2xl bg-white/10 px-4 py-3 backdrop-blur-sm">
                                <p class="text-[0.6875rem] font-semibold tracking-wide text-white/70 uppercase">{{ __('Hasil') }}</p>
                                <p class="mt-0.5 text-sm font-bold text-white">{{ $active_application->result?->label() ?? '—' }}</p>
                            </div>
                        </div>

                        <div class="relative z-10 mt-6 flex flex-wrap gap-3">
                            @if ($summary->current_module_id)
                                <x-actions.link-button :href="route('modules.show', $summary->current_module_id)" wire:navigate iconTrailing="arrow-right">
                                    {{ __('Lanjutkan Persiapan') }}
                                </x-actions.link-button>
                            @endif

                            @if ($scholarship)
                                <x-actions.link-button :href="route('scholarships.show', $scholarship)" wire:navigate>
                                    {{ __('Detail beasiswa') }}
                                </x-actions.link-button>
                            @endif
                        </div>
                    </div>
                @else
                    <x-ui.empty-state
                        icon="academic-cap"
                        :title="__('Belum ada beasiswa yang kamu pilih')"
                        :description="__('Pilih maksimal :count beasiswa supaya jalur belajarmu bisa disusun.', ['count' => $max_selections])"
                        :action-label="__('Jelajahi beasiswa')"
                        :action-url="route('scholarships.index')"
                        action-icon-trailing="arrow-right"
                        data-test="dashboard-scholarship-empty"
                    />
                @endif

                {{-- D. Next best action --}}
                <div class="relative overflow-hidden rounded-3xl border border-brand-amber/45 bg-brand-amber/12 p-5 shadow-soft dark:border-brand-amber/25 dark:bg-brand-amber/5" data-test="next-best-action">
                    <div class="flex items-start gap-4">
                        <x-ui.icon-badge :icon="$next_action['icon']" tone="amber" size="lg" solid />

                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-bold tracking-wide text-brand-gold uppercase dark:text-brand-amber">{{ __('Langkah berikutnya') }}</p>
                            <p class="mt-1 text-base font-bold text-zinc-900 dark:text-white">{{ $next_action['title'] }}</p>
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ $next_action['description'] }}</p>

                            @if ($next_action['action'] === 'advance')
                                <x-actions.button
                                    type="button"
                                    iconTrailing="arrow-right"
                                    class="mt-4"
                                    wire:click="advanceToNextScholarship"
                                    loading="dashboard.advance"
                                    data-test="advance-path"
                                >
                                    {{ $next_action['label'] }}
                                </x-actions.button>

                                <x-loading.inline loading="dashboard.advance" target="advanceToNextScholarship" class="mt-2" />

                                @error('transition')
                                    <p class="mt-2 text-xs font-semibold text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            @elseif ($next_action['url'])
                                <x-actions.link-button :href="$next_action['url']" wire:navigate iconTrailing="arrow-right" class="mt-4">
                                    {{ $next_action['label'] }}
                                </x-actions.link-button>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- D2. Path transition summary --}}
                @if ($path_completed)
                    <div class="rounded-3xl border border-brand-teal/20 bg-white p-5 shadow-soft dark:border-white/10 dark:bg-zinc-800/60" data-test="path-transition">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <p class="text-xs font-bold tracking-wide text-brand-teal uppercase dark:text-brand-teal-light">{{ __('Jalur selesai') }}</p>
                                <p class="mt-1 text-base font-bold text-zinc-900 dark:text-white">
                                    {{ __('Persiapan :name tuntas.', ['name' => $active_application->scholarship?->scholarship_name ?? __('beasiswa ini')]) }}
                                </p>
                                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">
                                    {{ __(':core Modul Inti yang sudah kamu selesaikan tetap selesai dan dipakai bersama. Kamu tidak perlu mengulanginya.', ['core' => $summary->core_completed_modules]) }}
                                </p>
                            </div>

                            <div class="shrink-0 rounded-2xl bg-brand-mint-soft/70 px-4 py-3 text-center">
                                <p class="text-2xl font-extrabold text-brand-teal dark:text-brand-teal-light">100%</p>
                                <p class="text-[0.625rem] font-semibold tracking-wide text-zinc-500 uppercase dark:text-zinc-400">{{ __('Jalur ini') }}</p>
                            </div>
                        </div>

                        @if ($next_application)
                            <div class="mt-4 rounded-2xl border border-brand-teal/15 bg-brand-teal/5 p-4">
                                <p class="text-sm font-bold text-zinc-900 dark:text-white">
                                    {{ __('Berikutnya: :name', ['name' => $next_application->scholarship?->scholarship_name ?? __('beasiswa berikutnya')]) }}
                                </p>
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                    @if ($next_template_count === 0)
                                        {{ __('Belum ada modul Template untuk beasiswa ini. Tim Skular sedang menyiapkannya.') }}
                                    @else
                                        {{ __(':count modul Template khusus menunggumu. Isinya terbuka saat jalurnya dimulai.', ['count' => $next_template_count]) }}
                                    @endif
                                </p>
                            </div>
                        @else
                            <p class="mt-4 rounded-2xl bg-zinc-50 px-4 py-3 text-sm text-zinc-600 dark:bg-white/5 dark:text-zinc-300">
                                {{ __('Tidak ada beasiswa lain di antreanmu saat ini. Riwayat dan fondasi belajarmu tetap tersimpan.') }}
                            </p>
                        @endif
                    </div>
                @endif

                {{-- E. Scholarship queue --}}
                @if ($queue->count() > 1)
                    <x-ui.card padding="none" class="overflow-hidden" data-test="scholarship-queue">
                        <div class="flex items-center justify-between gap-3 border-b border-brand-teal/10 px-5 py-4 dark:border-white/10">
                            <p class="text-sm font-bold text-zinc-900 dark:text-white">{{ __('Antrean beasiswamu') }}</p>
                            <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Diurutkan deadline terdekat') }}</span>
                        </div>

                        <ol class="flex flex-col divide-y divide-brand-teal/8 dark:divide-white/10">
                            @foreach ($queue as $index => $application)
                                @php $is_active = $active_application && $application->id === $active_application->id; @endphp

                                <li class="flex flex-wrap items-center gap-3 px-5 py-3.5" data-test="queue-item">
                                    <span @class([
                                        'flex size-8 shrink-0 items-center justify-center rounded-full text-xs font-bold',
                                        'brand-gradient text-brand-ink shadow-pop' => $is_active,
                                        'bg-zinc-100 text-zinc-500 dark:bg-white/10 dark:text-zinc-300' => ! $is_active,
                                    ])>{{ $index + 1 }}</span>

                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-semibold break-words text-zinc-800 dark:text-zinc-100">
                                            {{ $application->scholarship?->scholarship_name }}
                                        </p>
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                            {{ __('Tutup') }} {{ $application->scholarship?->closing_date?->translatedFormat('d M Y') ?? '—' }}
                                        </p>
                                    </div>

                                    @php
                                        $learning_state = $learning_states[$application->id] ?? 'queued';
                                        $learning_variant = $learning_state === 'completed' ? 'success' : ($learning_state === 'active' ? 'info' : 'neutral');
                                        $learning_label = $learning_state === 'completed' ? __('Jalur selesai') : ($learning_state === 'active' ? __('Sedang dipelajari') : __('Antre'));
                                    @endphp

                                    <x-ui.pill :variant="$learning_variant">{{ $learning_label }}</x-ui.pill>

                                    <x-ui.pill :variant="$application->status->getColor()">{{ $application->status->label() }}</x-ui.pill>
                                </li>
                            @endforeach
                        </ol>
                    </x-ui.card>
                @endif

                {{-- F. Roadmap summary; the full roadmap lives on /roadmap (App\Livewire\Roadmap). --}}
                <x-ui.card class="flex flex-col gap-4" data-test="dashboard-roadmap">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <x-ui.icon-badge icon="map" tone="teal" />
                            <h2 class="text-lg font-bold tracking-tight text-zinc-900">{{ __('Roadmap belajar') }}</h2>
                        </div>
                        <x-ui.pill variant="brand" icon="flag">{{ __(':percentage% selesai', ['percentage' => $summary->percentage]) }}</x-ui.pill>
                    </div>

                    @if ($roadmap_focus->active)
                        <div class="rounded-2xl bg-brand-teal/5 px-4 py-3">
                            <p class="text-xs font-bold tracking-wide text-brand-teal uppercase">{{ __('Fase aktif') }}</p>
                            <p class="mt-0.5 text-sm font-bold break-words text-zinc-900">{{ $roadmap_focus->active->title }}</p>
                            <div class="mt-2 flex items-center gap-3">
                                <x-ui.progress :value="$roadmap_focus->active->percentage" :showValue="false" size="sm" class="flex-1" />
                                <span class="shrink-0 text-xs font-semibold text-zinc-600">
                                    {{ __(':done/:total modul', ['done' => $roadmap_focus->active->completed_modules, 'total' => $roadmap_focus->active->total_modules]) }}
                                </span>
                            </div>
                            @if ($roadmap_focus->upcoming)
                                <p class="mt-2 inline-flex items-center gap-1 text-xs text-zinc-500">
                                    <x-icons.app-icon name="lock-closed" class="size-3.5" />
                                    {{ __('Berikutnya: :fase', ['fase' => $roadmap_focus->upcoming->title]) }}
                                </p>
                            @endif
                        </div>
                    @elseif ($roadmap_focus->isFinished())
                        <p class="rounded-2xl bg-brand-mint-soft/70 px-4 py-3 text-sm font-medium text-zinc-700">
                            {{ __('Semua fase di jalur ini sudah selesai. Kamu tetap bisa membuka ulang modulnya.') }}
                        </p>
                    @else
                        <p class="rounded-2xl bg-zinc-50 px-4 py-3 text-sm text-zinc-600" data-test="roadmap-empty">
                            {{ __('Roadmap belum tersedia. Pilih beasiswa atau tunggu tim Skular menambahkan modul.') }}
                        </p>
                    @endif

                    <x-actions.link-button :href="route('roadmap.show')" wire:navigate variant="outline" iconTrailing="arrow-right" class="justify-center sm:self-start" data-test="open-roadmap">
                        {{ __('Buka roadmap lengkap') }}
                    </x-actions.link-button>
                </x-ui.card>
            </section>
        </div>

        {{-- Right rail --}}
        <aside class="flex flex-col gap-4">
            {{-- Quick access --}}
            <section class="flex flex-col gap-3">
                <h2 class="text-sm font-bold tracking-wide text-zinc-500 uppercase dark:text-zinc-400">{{ __('Akses cepat') }}</h2>

                <div class="grid grid-cols-2 gap-3">
                    @foreach ($quick_links as $link)
                        <a
                            href="{{ $link['url'] }}"
                            wire:navigate
                            class="sk-lift group flex flex-col gap-3 rounded-3xl border border-brand-teal/10 bg-white p-4 shadow-soft dark:border-white/10 dark:bg-zinc-800/60"
                        >
                            <x-ui.icon-badge :icon="$link['icon']" :tone="$link['tone']" />
                            <span class="text-sm font-semibold text-zinc-800 group-hover:text-brand-teal dark:text-zinc-100 dark:group-hover:text-brand-teal-light">
                                {{ $link['label'] }}
                            </span>
                        </a>
                    @endforeach
                </div>

                <x-actions.link-button :href="route('profile.edit')" wire:navigate variant="outline" icon="user-circle" class="justify-center">
                    {{ __('Profil & Akun') }}
                </x-actions.link-button>
            </section>

            @if ($completed_paths->isNotEmpty())
                <x-ui.card class="flex flex-col gap-2" data-test="completed-paths">
                    <p class="text-sm font-bold text-zinc-900 dark:text-white">{{ __('Riwayat jalur belajar') }}</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Jalur yang sudah selesai tetap tersimpan beserta jawaban dan feedback-nya.') }}</p>

                    <ul class="flex flex-col divide-y divide-brand-teal/8 dark:divide-white/10">
                        @foreach ($completed_paths as $application)
                            <li class="flex items-center justify-between gap-2 py-2.5">
                                <div class="min-w-0 flex-1">
                                    <p class="break-words text-sm text-zinc-700 dark:text-zinc-200">{{ $application->scholarship?->scholarship_name }}</p>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $application->learning_completed_at?->translatedFormat('d M Y') }}</p>
                                </div>
                                <x-ui.pill variant="success" icon="check">{{ __('Selesai') }}</x-ui.pill>
                            </li>
                        @endforeach
                    </ul>
                </x-ui.card>
            @endif

            @if ($completed_applications->isNotEmpty())
                <x-ui.card class="flex flex-col gap-2" data-test="completed-applications">
                    <p class="text-sm font-bold text-zinc-900 dark:text-white">{{ __('Sudah selesai diproses') }}</p>

                    <ul class="flex flex-col divide-y divide-brand-teal/8 dark:divide-white/10">
                        @foreach ($completed_applications as $application)
                            <li class="flex items-center justify-between gap-2 py-2.5">
                                <div class="min-w-0 flex-1">
                                    <p class="break-words text-sm text-zinc-700 dark:text-zinc-200">{{ $application->scholarship?->scholarship_name }}</p>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $application->result_reported_at?->translatedFormat('d M Y') }}</p>
                                </div>
                                <x-ui.pill :variant="($application->result?->getColor() ?? 'neutral')">
                                    {{ $application->result?->label() ?? '—' }}
                                </x-ui.pill>
                            </li>
                        @endforeach
                    </ul>
                </x-ui.card>
            @endif
        </aside>
    </div>

    {{-- Onboarding coach marks for this page (opens once; replay from /panduan). --}}
    <livewire:onboarding.product-tour tour="dashboard" />
</div>
