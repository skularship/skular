@props([
    'showName' => true,
])

@php
    $user = auth()->user();
@endphp

@unless ($showName)
    {{-- Mobile header: the button sits at the top edge, so the menu opens downward. --}}
    <div
        x-data="{ open: false }"
        x-on:click.outside="open = false"
        x-on:keydown.escape.window="open = false"
        class="relative"
    >
        <button
            type="button"
            x-on:click="open = ! open"
            aria-haspopup="true"
            x-bind:aria-expanded="open"
            aria-label="{{ __('Menu profil') }}"
            class="group flex size-8 items-center justify-center rounded-lg transition hover:bg-zinc-800/5 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-orange dark:hover:bg-white/15"
        >
            <x-display.avatar :initials="$user->initials()" :name="$user->name" />
        </button>

        <div
            x-show="open"
            x-cloak
            x-transition
            x-transition.origin.top.right
            class="absolute end-0 top-full z-60 mt-2 min-w-64 rounded-xl border border-zinc-200 bg-white p-1.5 shadow-lg dark:border-zinc-600 dark:bg-zinc-700"
        >
            <x-navigation.user-menu-items :on-click-string="'open = false'" />
        </div>
    </div>
@endunless

@if ($showName)
    {{-- Sidebar: the button sits at the bottom edge, so the menu must open upward to avoid clipping. --}}
    <div
        x-data="{ open: false }"
        x-on:click.outside="open = false"
        x-on:keydown.escape.window="open = false"
        class="relative"
    >
        <button
            type="button"
            x-on:click="open = ! open"
            data-test="sidebar-menu-button"
            aria-haspopup="true"
            x-bind:aria-expanded="open"
            class="group flex w-full items-center rounded-lg p-1 transition hover:bg-zinc-800/5 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-orange dark:hover:bg-white/15"
        >
            <x-display.avatar :initials="$user->initials()" :name="$user->name" />

            <span class="mx-2 min-w-0 flex-1 truncate text-start text-sm font-medium text-zinc-500 group-hover:text-zinc-800 dark:text-white/80 dark:group-hover:text-white">
                {{ $user->name }}
            </span>

            <span class="flex size-8 shrink-0 items-center justify-center text-zinc-400 transition group-hover:text-zinc-800 dark:text-white/80 dark:group-hover:text-white">
                <x-icons.app-icon name="chevron-down" variant="m" class="size-4 transition-transform duration-200" x-bind:class="open ? 'rotate-180' : ''" />
            </span>
        </button>

        <div
            x-show="open"
            x-cloak
            x-transition
            x-transition.origin.bottom.left
            class="absolute start-0 bottom-full z-60 mb-2 min-w-64 rounded-xl border border-zinc-200 bg-white p-1.5 shadow-lg dark:border-zinc-600 dark:bg-zinc-700"
        >
            <x-navigation.user-menu-items :on-click-string="'open = false'" />
        </div>
    </div>
@endif
