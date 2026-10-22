@php
    $mobile = $mobile ?? false;

    $user = auth()->user();

    // Grouped by intent so the rail reads as a learning journey, not a flat list.
    $groups = [
        [
            'label' => __('Ruang Belajar'),
            'links' => [
                ['route' => 'dashboard', 'label' => __('Beranda'), 'icon' => 'home', 'pattern' => 'dashboard'],
                ['route' => 'library.index', 'label' => __('Perpustakaan'), 'icon' => 'book-open', 'pattern' => 'library.*'],
            ],
        ],
        [
            'label' => __('Beasiswa'),
            'links' => [
                ['route' => 'profiling.recommendation', 'label' => __('Rekomendasi'), 'icon' => 'sparkles', 'pattern' => 'profiling.recommendation'],
                ['route' => 'scholarships.index', 'label' => __('Semua Beasiswa'), 'icon' => 'magnifying-glass', 'pattern' => 'scholarships.index'],
                ['route' => 'scholarships.tracker', 'label' => __('Beasiswa Saya'), 'icon' => 'document-arrow-up', 'pattern' => 'scholarships.tracker'],
            ],
        ],
        [
            'label' => __('Akun'),
            'links' => [
                ['route' => 'profiling', 'label' => __('Profil & Profiling'), 'icon' => 'identification', 'pattern' => 'profiling'],
                ['route' => 'cv.show', 'label' => __('CV Saya'), 'icon' => 'document-text', 'pattern' => 'cv.show'],
            ],
        ],
    ];
@endphp

<div class="flex min-h-10 items-center justify-between gap-2 md:mb-5 md:mt-3">
    <x-brand.app-logo href="{{ route('dashboard') }}" class="mx-auto text-center" wire:navigate />

    @if ($mobile)
        <button
            type="button"
            x-on:click="$store.sidebar.open = false"
            aria-label="{{ __('Close sidebar') }}"
            class="-me-1 flex size-8 shrink-0 items-center justify-center rounded-lg text-zinc-500 transition hover:bg-zinc-800/5 hover:text-zinc-800 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-accent dark:text-white/80 dark:hover:bg-white/[7%] dark:hover:text-white"
        >
            <x-icons.app-icon name="x-mark" class="size-5" />
        </button>
    @endif
</div>

<nav class="flex flex-col gap-5 md:mt-5" aria-label="{{ __('Navigasi utama') }}">
    @foreach ($groups as $group)
        <div class="flex flex-col gap-1">
            <p class="px-3 text-[0.6875rem] font-bold tracking-widest text-zinc-400 uppercase dark:text-zinc-500">
                {{ $group['label'] }}
            </p>

            <div class="flex min-w-0 flex-col gap-0.5">
                @foreach ($group['links'] as $link)
                    @php
                        $is_active = request()->routeIs($link['pattern']);
                        $icon_classes = $is_active
                            ? 'size-[1.15rem] text-brand-ink'
                            : 'size-[1.15rem] text-brand-teal transition group-hover:scale-110 dark:text-brand-teal-light';
                    @endphp

                    <a
                        href="{{ route($link['route']) }}"
                        wire:navigate
                        x-on:click="$store.sidebar.open = false"
                        @if ($is_active) aria-current="page" @endif
                        @class([
                            'group relative flex w-full items-center gap-3 rounded-2xl px-3 py-2.5 text-sm font-semibold transition',
                            'brand-gradient text-brand-ink shadow-pop' => $is_active,
                            'text-zinc-600 hover:bg-brand-teal/8 hover:text-brand-teal dark:text-zinc-300 dark:hover:bg-white/[7%] dark:hover:text-brand-teal-light' => ! $is_active,
                        ])
                    >
                        <x-icons.app-icon :name="$link['icon']" class="{{ $icon_classes }}" />
                        {{ $link['label'] }}

                        @if ($is_active)
                            <x-icons.app-icon name="chevron-right" class="ms-auto size-3.5 text-brand-ink/70" />
                        @endif
                    </a>
                @endforeach
            </div>
        </div>
    @endforeach

    @if (! $user?->hasCompletedProfile())
        <div class="rounded-2xl border border-brand-amber/50 bg-brand-amber/12 p-3 dark:border-brand-amber/25 dark:bg-brand-amber/5">
            <p class="flex items-center gap-1.5 text-xs font-bold text-zinc-800 dark:text-zinc-100">
                <x-icons.app-icon name="sparkles" class="size-3.5 text-brand-orange" />
                {{ __('Profil belum lengkap') }}
            </p>
            <p class="mt-1 text-xs text-zinc-600 dark:text-zinc-400">
                {{ __('Selesaikan profiling agar rekomendasi beasiswamu muncul.') }}
            </p>

            <x-actions.link-button :href="route('profiling')" wire:navigate size="sm" class="mt-3 w-full">
                {{ __('Lanjutkan profiling') }}
            </x-actions.link-button>
        </div>
    @endif
</nav>

<div class="flex-1"></div>

@if ($user?->canAccessPanel(filament()->getPanel('admin')))
    <nav class="flex flex-col">
        <a
            href="{{ route('filament.admin.pages.dashboard') }}"
            x-on:click="$store.sidebar.open = false"
            class="flex w-full items-center gap-3 rounded-2xl border border-transparent px-3 py-2.5 text-sm font-medium text-zinc-500 transition hover:bg-zinc-800/5 hover:text-zinc-800 dark:text-white/70 dark:hover:bg-white/[7%] dark:hover:text-white"
        >
            <x-icons.app-icon name="shield-check" class="size-[1.15rem]" />
            {{ __('Panel Admin') }}
        </a>
    </nav>
@endif

<div class="mt-2 rounded-2xl border border-brand-teal/12 bg-white/70 p-1 dark:border-white/10 dark:bg-white/5">
    <x-navigation.user-menu :show-name="true" />
</div>
