<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body x-data class="min-h-screen bg-white dark:bg-zinc-800">
        <header class="sticky top-0 z-30 flex min-h-14 items-center border-b border-zinc-200 bg-zinc-50 px-6 lg:px-8 dark:border-zinc-700 dark:bg-zinc-900">
            <button
                type="button"
                x-on:click="$store.sidebar.open = true"
                aria-label="{{ __('Toggle sidebar') }}"
                class="-ms-1 me-2 flex size-8 shrink-0 items-center justify-center rounded-lg text-zinc-500 transition hover:bg-zinc-800/5 hover:text-zinc-800 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-accent lg:hidden dark:text-white/80 dark:hover:bg-white/[7%] dark:hover:text-white"
            >
                <x-icons.app-icon name="bars-2" class="size-5" />
            </button>

            <x-brand.app-logo href="{{ route('dashboard') }}" wire:navigate />

            <nav class="-mb-px hidden items-center gap-0.5 lg:flex">
                <a
                    href="{{ route('dashboard') }}"
                    wire:navigate
                    @class([
                        'flex items-center gap-2 rounded-lg border-b-2 px-3 py-2 text-sm font-medium',
                        'border-[var(--color-accent)] text-[var(--color-accent-content)]' => request()->routeIs('dashboard'),
                        'border-transparent text-zinc-500 hover:text-zinc-800 dark:text-white/70 dark:hover:text-white' => ! request()->routeIs('dashboard'),
                    ])
                >
                    <x-icons.app-icon name="layout-grid" class="size-4" />
                    {{ __('Dashboard') }}
                </a>
            </nav>

            <div class="flex-1"></div>

            <nav class="flex items-center gap-0.5">
                <button
                    type="button"
                    title="{{ __('Search') }}"
                    aria-label="{{ __('Search') }}"
                    class="flex size-10 items-center justify-center rounded-lg text-zinc-500 transition hover:bg-zinc-800/5 hover:text-zinc-800 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-accent dark:text-white/70 dark:hover:bg-white/[7%] dark:hover:text-white"
                >
                    <x-icons.app-icon name="magnifying-glass" class="size-5" />
                </button>

                <a
                    href="https://github.com/laravel/livewire-starter-kit"
                    target="_blank"
                    rel="noreferrer"
                    title="{{ __('Repository') }}"
                    aria-label="{{ __('Repository') }}"
                    class="hidden size-10 items-center justify-center rounded-lg text-zinc-500 transition hover:bg-zinc-800/5 hover:text-zinc-800 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-accent lg:flex dark:text-white/70 dark:hover:bg-white/[7%] dark:hover:text-white"
                >
                    <x-icons.app-icon name="folder-git-2" class="size-5" />
                </a>

                <a
                    href="https://laravel.com/docs/starter-kits#livewire"
                    target="_blank"
                    rel="noreferrer"
                    title="{{ __('Documentation') }}"
                    aria-label="{{ __('Documentation') }}"
                    class="hidden size-10 items-center justify-center rounded-lg text-zinc-500 transition hover:bg-zinc-800/5 hover:text-zinc-800 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-accent lg:flex dark:text-white/70 dark:hover:bg-white/[7%] dark:hover:text-white"
                >
                    <x-icons.app-icon name="book-open-text" class="size-5" />
                </a>
            </nav>

            <div class="ms-2">
                <x-navigation.user-menu :show-name="false" />
            </div>
        </header>

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
            class="fixed inset-y-0 start-0 z-50 flex w-72 max-w-[80vw] flex-col gap-4 overflow-y-auto overscroll-contain border-e border-zinc-200 bg-zinc-50 p-4 lg:hidden dark:border-zinc-700 dark:bg-zinc-900"
        >
            @include('partials.sidebar-nav', ['mobile' => true])
        </aside>

        <main class="min-w-0 flex-1 p-6 lg:p-8">
            {{ $slot }}
        </main>

        @persist('toast')
            @include('partials.toast')
        @endpersist
    </body>
</html>
