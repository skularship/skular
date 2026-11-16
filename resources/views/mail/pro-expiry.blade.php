<x-mail::message>
# {{ __('Halo :name,', ['name' => $subscription->user->name]) }}

{{ __('Paket Pro kamu berakhir pada :date (:n hari lagi). Dokumen dan checklistmu tetap tersimpan. Perpanjang sekarang dan masa aktifnya disambung dari tanggal itu, tidak ada hari yang hilang.', [
    'date' => $subscription->active_until->translatedFormat('j F Y'),
    'n' => $daysLeft,
]) }}

<x-mail::button :url="route('premium')">
{{ __('Perpanjang Pro') }}
</x-mail::button>

{{ __('Salam,') }}
Skular
</x-mail::message>
