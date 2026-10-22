<div class="flex items-start max-md:flex-col">
    <div class="w-full pb-4 md:me-10 md:w-[220px]">
        <nav aria-label="{{ __('Settings') }}" class="flex flex-col">
            <a
                href="{{ route('profile.edit') }}"
                wire:navigate
                @class([
                    'my-px flex h-10 w-full items-center gap-3 rounded-lg px-3 py-0 text-start text-sm font-medium lg:h-8',
                    'bg-zinc-800/[4%] text-[var(--color-accent-content)] dark:bg-white/[7%]' => request()->routeIs('profile.edit'),
                    'text-zinc-500 hover:bg-zinc-800/[4%] hover:text-zinc-800 dark:text-white/80 dark:hover:bg-white/[7%] dark:hover:text-white' => ! request()->routeIs('profile.edit'),
                ])
            >
                {{ __('Profile') }}
            </a>

            <a
                href="{{ route('security.edit') }}"
                wire:navigate
                @class([
                    'my-px flex h-10 w-full items-center gap-3 rounded-lg px-3 py-0 text-start text-sm font-medium lg:h-8',
                    'bg-zinc-800/[4%] text-[var(--color-accent-content)] dark:bg-white/[7%]' => request()->routeIs('security.edit'),
                    'text-zinc-500 hover:bg-zinc-800/[4%] hover:text-zinc-800 dark:text-white/80 dark:hover:bg-white/[7%] dark:hover:text-white' => ! request()->routeIs('security.edit'),
                ])
            >
                {{ __('Security') }}
            </a>

            <a
                href="{{ route('appearance.edit') }}"
                wire:navigate
                @class([
                    'my-px flex h-10 w-full items-center gap-3 rounded-lg px-3 py-0 text-start text-sm font-medium lg:h-8',
                    'bg-zinc-800/[4%] text-[var(--color-accent-content)] dark:bg-white/[7%]' => request()->routeIs('appearance.edit'),
                    'text-zinc-500 hover:bg-zinc-800/[4%] hover:text-zinc-800 dark:text-white/80 dark:hover:bg-white/[7%] dark:hover:text-white' => ! request()->routeIs('appearance.edit'),
                ])
            >
                {{ __('Appearance') }}
            </a>
        </nav>
    </div>

    <hr class="w-full border-t border-zinc-200 dark:border-zinc-700 md:hidden" />

    <div class="flex-1 self-stretch max-md:pt-6">
        <x-display.heading>{{ $heading ?? '' }}</x-display.heading>
        <x-display.subheading>{{ $subheading ?? '' }}</x-display.subheading>

        <div class="mt-5 w-full max-w-lg">
            {{ $slot }}
        </div>
    </div>
</div>
