{{-- Links to the four legal pages plus the "Pengaturan cookie" button that reopens the cookie banner (<x-legal.links>). Props: linkClass (classes for each link), cookieSettings (show the cookie settings button). --}}
@props([
    'linkClass' => 'text-zinc-300 transition hover:text-brand-amber',
    'cookieSettings' => true,
])

<nav {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-x-5 gap-y-2 text-sm']) }} aria-label="{{ __('Informasi legal') }}" data-test="legal-links">
    @foreach (\App\Enums\LegalDocument::cases() as $legalDocument)
        <a href="{{ route($legalDocument->routeName()) }}" class="{{ $linkClass }}">{{ $legalDocument->label() }}</a>
    @endforeach

    @if ($cookieSettings)
        <button type="button" data-cookie-settings class="{{ $linkClass }} cursor-pointer text-start">{{ __('Pengaturan cookie') }}</button>
    @endif
</nav>
