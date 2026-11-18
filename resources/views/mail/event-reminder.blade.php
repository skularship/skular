{{-- Event reminder email (App\Mail\EventReminderMail): H-1 day or H-1 hour, meeting link, and "Tambah ke kalender". --}}
@php
    $event = $registration->event;
@endphp
<x-mail::message>
# {{ __('Halo :name,', ['name' => $registration->user->name]) }}

{{ $kind === 'hour'
    ? __(':title dimulai 1 jam lagi.', ['title' => $event->title])
    : __(':title berlangsung besok.', ['title' => $event->title]) }}

**{{ $event->startsAtLabel() }}**

@if ($event->location_url)
<x-mail::button :url="$event->location_url">
{{ __('Masuk ke acara') }}
</x-mail::button>
@else
<x-mail::button :url="route('events.show', $event)">
{{ __('Buka halaman acara') }}
</x-mail::button>
@endif

[{{ __('Tambah ke kalender') }}]({{ route('events.calendar', $event) }})

{{ __('Sampai jumpa di acara,') }}
Skular
</x-mail::message>
