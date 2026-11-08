{{-- Auth layout (<x-layouts::auth>) used by the Fortify Livewire screens; delegates to layouts/auth/simple. --}}
<x-layouts::auth.simple :title="$title ?? null">
    {{ $slot }}
</x-layouts::auth.simple>
