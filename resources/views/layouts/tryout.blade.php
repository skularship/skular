<!DOCTYPE html>
{{-- Focus layout of the Try Out runner (#[Layout('layouts.tryout')]): no sidebar or bottom navigation so the
     mentee only sees the test. The runner renders its own teal header with timer. Cookie banner and legal links
     are kept (AGENTS: every layout). Data: optional $title. --}}
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['robots' => 'noindex, nofollow', 'title' => $title ?? null])
    </head>
    <body x-data class="min-h-screen bg-brand-cream text-zinc-800 antialiased">
        @include('partials.cookie-consent')

        {{ $slot }}

        <footer class="px-4 pt-6 pb-10">
            <x-legal.links class="justify-center gap-x-3 gap-y-1 text-xs" link-class="text-zinc-500 hover:text-brand-teal" />
        </footer>

        @persist('toast')
            @include('partials.toast')
        @endpersist
    </body>
</html>
