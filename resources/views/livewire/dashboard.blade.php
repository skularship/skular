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
        >
            <x-actions.link-button :href="route('profiling')" wire:navigate iconTrailing="arrow-right">
                {{ __('Mulai profiling') }}
            </x-actions.link-button>
        </x-ui.empty-state>
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
            :label="__('Rata-rata skor')"
            :value="$summary->average_score > 0 ? $summary->average_score : '—'"
            :hint="__('Dari feedback AI')"
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
                    >
                        <x-actions.link-button :href="route('scholarships.index')" wire:navigate iconTrailing="arrow-right">
                            {{ __('Jelajahi beasiswa') }}
                        </x-actions.link-button>
                    </x-ui.empty-state>
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
                                    {{ __('Template khusus yang akan kamu pelajari:') }}
                                </p>

                                @if ($next_templates->isEmpty())
                                    <p class="mt-1 text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                        {{ __('Belum ada modul Template untuk beasiswa ini. Admin sedang menyiapkannya.') }}
                                    </p>
                                @else
                                    <ul class="mt-2 flex flex-col gap-1">
                                        @foreach ($next_templates as $template)
                                            <li class="flex items-start gap-2 text-xs text-zinc-600 dark:text-zinc-300">
                                                <x-icons.app-icon name="document-text" class="mt-0.5 size-3.5 shrink-0 text-brand-teal dark:text-brand-teal-light" />
                                                {{ $template->title }}
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
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

                {{-- F. Roadmap --}}
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-lg font-bold tracking-tight text-zinc-900 dark:text-white">{{ __('Roadmap belajar') }}</h2>
                    <x-ui.pill variant="brand" icon="flag">{{ __(':percentage% selesai', ['percentage' => $summary->percentage]) }}</x-ui.pill>
                </div>

                <p class="-mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __('Modul Inti dipakai bersama semua beasiswa, jadi sekali selesai akan tetap selesai di jalur berikutnya. Modul Template mengikuti beasiswa aktif.') }}
                </p>

                @if ($active_application && $summary->template_total_modules === 0)
                    <p class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs font-medium text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">
                        {{ __('Belum ada modul Template untuk beasiswa ini. Kamu tetap bisa menyelesaikan Modul Inti; admin sedang menyiapkan materi khusus beasiswanya.') }}
                    </p>
                @endif

                @forelse ($roadmap as $fase)
                    <x-ui.card padding="none" class="overflow-hidden">
                        <div class="flex flex-wrap items-center justify-between gap-3 bg-linear-to-r from-brand-teal/8 to-brand-amber/8 px-5 py-4 dark:from-brand-teal/15 dark:to-brand-amber/10">
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-zinc-900 dark:text-white">{{ $fase->title }}</p>
                                @if (filled($fase->subtitle))
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $fase->subtitle }}</p>
                                @endif
                            </div>

                            <div class="flex min-w-[9rem] flex-col gap-1.5">
                                <span class="text-xs font-semibold text-zinc-600 dark:text-zinc-300">
                                    {{ __(':done/:total modul', ['done' => $fase->completed_modules, 'total' => $fase->total_modules]) }}
                                </span>
                                <x-ui.progress :value="$fase->percentage" :showValue="false" size="sm" />
                            </div>
                        </div>

                        <ol class="flex flex-col p-5">
                            @foreach ($fase->modules as $module)
                                <li class="relative flex gap-4 pb-6 last:pb-0">
                                    @if (! $loop->last)
                                        <span @class([
                                            'absolute top-10 bottom-0 left-[1.125rem] w-0.5 rounded-full',
                                            'brand-gradient' => $module->isCompleted(),
                                            'bg-zinc-200 dark:bg-zinc-700' => ! $module->isCompleted(),
                                        ])></span>
                                    @endif

                                    <span @class([
                                        'relative z-10 flex size-9 shrink-0 items-center justify-center rounded-full border-2 shadow-sm transition',
                                        'brand-gradient border-transparent text-brand-ink' => $module->isCompleted(),
                                        'border-brand-teal bg-white text-brand-teal ring-4 ring-brand-teal/15 dark:bg-zinc-800 dark:text-brand-teal-light' => $module->isInProgress(),
                                        'border-brand-teal/60 bg-white text-brand-teal dark:bg-zinc-800 dark:text-brand-teal-light' => ! $module->isCompleted() && ! $module->isInProgress() && $module->isUnlocked(),
                                        'border-zinc-200 bg-zinc-50 text-zinc-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-500' => $module->isLocked(),
                                    ])>
                                        <x-icons.app-icon :name="$module->statusIcon()" class="size-4" />
                                    </span>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p @class([
                                                'text-sm font-semibold',
                                                'text-zinc-900 dark:text-white' => $module->isUnlocked(),
                                                'text-zinc-400 dark:text-zinc-500' => $module->isLocked(),
                                            ])>
                                                @if ($module->isUnlocked())
                                                    <a href="{{ route('modules.show', $module->id) }}" wire:navigate class="transition hover:text-brand-teal dark:hover:text-brand-teal-light">
                                                        {{ $module->modul_order }}. {{ $module->title }}
                                                    </a>
                                                @else
                                                    {{ $module->modul_order }}. {{ $module->title }}
                                                @endif
                                            </p>

                                            <x-ui.pill variant="info">{{ $module->category->label() }}</x-ui.pill>

                                            @if ($module->isShared())
                                                <span class="inline-flex items-center gap-1 text-xs font-semibold text-brand-teal dark:text-brand-teal-light">
                                                    <x-icons.app-icon name="share" class="size-3.5" />
                                                    {{ __('Dipakai bersama') }}
                                                </span>
                                            @endif

                                            @if ($module->duration)
                                                <span class="inline-flex items-center gap-1 text-xs text-zinc-500 dark:text-zinc-400">
                                                    <x-icons.app-icon name="clock" class="size-3.5" />
                                                    {{ __(':minutes menit', ['minutes' => $module->duration]) }}
                                                </span>
                                            @endif
                                        </div>

                                        <div class="mt-1.5 flex flex-wrap items-center gap-2">
                                            <x-ui.pill :variant="$module->isCompleted() ? 'success' : ($module->isInProgress() ? 'info' : 'neutral')" :icon="$module->statusIcon()">{{ $module->status->label() }}</x-ui.pill>

                                            @if ($module->score !== null)
                                                <x-ui.pill variant="success" icon="star">{{ __('Skor :score', ['score' => $module->score]) }}</x-ui.pill>
                                            @endif

                                            @if ($module->isLocked())
                                                <span class="inline-flex items-center gap-1 text-xs text-zinc-500 dark:text-zinc-400">
                                                    <x-icons.app-icon name="lock-closed" class="size-3.5" />
                                                    {{ __('Selesaikan modul sebelumnya untuk membuka') }}
                                                </span>
                                            @else
                                                <a href="{{ route('modules.show', $module->id) }}" wire:navigate class="inline-flex items-center gap-1 text-xs font-bold text-brand-teal transition hover:underline dark:text-brand-teal-light">
                                                    {{ $module->isCompleted() ? __('Ulangi modul') : ($module->isInProgress() ? __('Lanjutkan modul') : __('Mulai modul')) }}
                                                    <x-icons.app-icon name="arrow-right" class="size-3" />
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    </x-ui.card>
                @empty
                    <x-ui.empty-state
                        icon="map"
                        :title="__('Roadmap belum tersedia')"
                        :description="__('Pilih beasiswa atau tunggu admin menambahkan modul pembelajaran.')"
                    />
                @endforelse
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
</div>
