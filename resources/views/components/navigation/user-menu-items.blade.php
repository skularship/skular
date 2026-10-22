@props([
    'onClickString' => 'open = false',
])

@php
    $user = auth()->user();
@endphp

<div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
    <x-display.avatar :initials="$user->initials()" :name="$user->name" />

    <div class="grid flex-1 text-start text-sm leading-tight">
        <span class="truncate font-medium text-zinc-800 dark:text-white">{{ $user->name }}</span>
        <span class="truncate text-zinc-500 dark:text-zinc-300">{{ $user->email }}</span>
    </div>
</div>

<div class="my-1 h-px bg-zinc-200 dark:bg-zinc-600"></div>

<a
    href="{{ route('profile.edit') }}"
    wire:navigate
    x-on:click="{{ $onClickString }}"
    class="flex w-full items-center gap-3 rounded-lg px-2 py-1.5 text-start text-sm font-medium text-zinc-800 transition hover:bg-brand-amber/25 dark:text-white dark:hover:bg-white/10"
>
    <x-icons.app-icon name="cog" variant="m" class="size-4 text-zinc-400 dark:text-white/60" />
    {{ __('Settings') }}
</a>

<a
    href="{{ route('cv.show') }}"
    wire:navigate
    x-on:click="{{ $onClickString }}"
    class="flex w-full items-center gap-3 rounded-lg px-2 py-1.5 text-start text-sm font-medium text-zinc-800 transition hover:bg-brand-amber/25 dark:text-white dark:hover:bg-white/10"
>
    <x-icons.app-icon name="document-text" variant="m" class="size-4 text-zinc-400 dark:text-white/60" />
    {{ __('CV Saya') }}
</a>

<div class="my-1 h-px bg-zinc-200 dark:bg-zinc-600"></div>

<form method="POST" action="{{ route('logout') }}" class="w-full">
    @csrf
    <button
        type="submit"
        data-test="logout-button"
        class="flex w-full cursor-pointer items-center gap-3 rounded-lg px-2 py-1.5 text-start text-sm font-medium text-zinc-800 transition hover:bg-red-50 hover:text-red-600 dark:text-white dark:hover:bg-red-950/40 dark:hover:text-red-300"
    >
        <x-icons.app-icon name="arrow-right-start-on-rectangle" variant="m" class="size-4 text-zinc-400 dark:text-white/60" />
        {{ __('Log out') }}
    </button>
</form>
