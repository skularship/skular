<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    {{-- The empty x-data root is required: the sidebar toggle and its x-show
         panel live outside any component, and Alpine only evaluates directives
         that sit inside an Alpine scope. --}}
    <body x-data class="min-h-screen bg-linear-to-b from-warm-cream to-brand-amber/10 text-zinc-800 antialiased dark:bg-linear-to-b dark:from-zinc-900 dark:to-zinc-950 dark:text-zinc-100">
        <div class="flex min-h-screen">
            <!-- Desktop Sidebar -->
            <aside class="sticky top-0 hidden h-dvh w-72 shrink-0 flex-col gap-4 overflow-y-auto overscroll-contain border-e border-brand-teal/12 bg-warm-cream/85 p-4 backdrop-blur lg:flex dark:border-white/10 dark:bg-zinc-900/85">
                @include('partials.sidebar-nav')

                <p class="pt-2 text-center text-[0.625rem] font-medium tracking-wide text-zinc-400 uppercase dark:text-zinc-500">
                    {{ __('Skular · belajar beasiswa dengan AI') }}
                </p>
            </aside>

            <!-- Mobile Sidebar Backdrop -->
            <div
                x-cloak
                x-show="$store.sidebar.open"
                x-transition.opacity
                x-on:click="$store.sidebar.open = false"
                class="fixed inset-0 z-40 bg-zinc-950/50 backdrop-blur-sm lg:hidden"
            ></div>

            <!-- Mobile Sidebar -->
            <aside
                x-cloak
                x-show="$store.sidebar.open"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="-translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="-translate-x-full"
                class="fixed inset-y-0 start-0 z-50 flex w-72 max-w-[80vw] flex-col gap-4 overflow-y-auto overscroll-contain border-e border-brand-teal/15 bg-warm-cream p-4 lg:hidden dark:border-white/10 dark:bg-zinc-900"
            >
                @include('partials.sidebar-nav', ['mobile' => true])
            </aside>

            <div class="flex min-w-0 flex-1 flex-col">
                <!-- Mobile Header -->
                <header class="sticky top-0 z-30 flex min-h-14 items-center gap-2 border-b border-brand-teal/12 bg-warm-cream/90 px-4 backdrop-blur lg:hidden dark:border-white/10 dark:bg-zinc-900/90">
                    <button
                        type="button"
                        x-on:click="$store.sidebar.open = true"
                        aria-label="{{ __('Toggle sidebar') }}"
                        class="-ms-1 flex size-9 shrink-0 items-center justify-center rounded-xl text-zinc-500 transition hover:bg-zinc-800/5 hover:text-zinc-800 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-accent dark:text-white/80 dark:hover:bg-white/[7%] dark:hover:text-white"
                    >
                        <x-icons.app-icon name="bars-2" class="size-5" />
                    </button>

                    <x-brand.app-logo href="{{ route('dashboard') }}" wire:navigate />

                    <div class="flex-1"></div>

                    <x-navigation.user-menu :show-name="false" />
                </header>

                <main class="min-w-0 flex-1 px-4 pt-6 pb-28 sm:px-6 lg:p-8">
                    {{ $slot }}
                </main>
            </div>
        </div>

        <!-- Mobile primary navigation -->
        @php
            $bottom_links = [
                ['route' => 'dashboard', 'label' => __('Beranda'), 'icon' => 'home', 'pattern' => 'dashboard'],
                ['route' => 'scholarships.index', 'label' => __('Beasiswa'), 'icon' => 'magnifying-glass', 'pattern' => 'scholarships.index'],
                ['route' => 'library.index', 'label' => __('Belajar'), 'icon' => 'book-open', 'pattern' => 'library.*'],
                ['route' => 'cv.show', 'label' => __('CV'), 'icon' => 'document-text', 'pattern' => 'cv.show'],
                ['route' => 'profiling', 'label' => __('Profil'), 'icon' => 'user-circle', 'pattern' => 'profiling'],
            ];
        @endphp

        <nav
            class="fixed inset-x-0 bottom-0 z-40 border-t border-brand-teal/12 bg-warm-cream/95 pb-[env(safe-area-inset-bottom)] backdrop-blur lg:hidden dark:border-white/10 dark:bg-zinc-900/95"
            aria-label="{{ __('Navigasi cepat') }}"
        >
            <ul class="mx-auto flex max-w-lg items-stretch justify-between px-2">
                @foreach ($bottom_links as $link)
                    @php $is_active = request()->routeIs($link['pattern']); @endphp

                    <li class="flex-1">
                        <a
                            href="{{ route($link['route']) }}"
                            wire:navigate
                            @if ($is_active) aria-current="page" @endif
                            @class([
                                'flex flex-col items-center gap-1 rounded-2xl px-1 py-2.5 text-[0.6875rem] font-semibold transition',
                                'text-brand-teal dark:text-brand-teal-light' => $is_active,
                                'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-white' => ! $is_active,
                            ])
                        >
                            <span @class([
                                'flex size-9 items-center justify-center rounded-2xl transition',
                                'brand-gradient text-brand-ink shadow-pop' => $is_active,
                            ])>
                                <x-icons.app-icon :name="$link['icon']" class="size-5" />
                            </span>
                            {{ $link['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

        @persist('toast')
            @include('partials.toast', ['above_mobile_nav' => true])
        @endpersist
    </body>
</html>
