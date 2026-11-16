<x-mail::message>
# {{ __('Halo :name,', ['name' => $application->user->name]) }}

{{ __(':scholarship tutup pada :date (:n hari lagi). Cek berkasmu sekarang supaya tidak terburu-buru di hari terakhir.', [
    'scholarship' => $application->scholarship->scholarship_name,
    'date' => $application->scholarship->closing_date?->translatedFormat('j F Y'),
    'n' => $daysLeft,
]) }}

<x-mail::button :url="route('scholarships.tracker')">
{{ __('Buka beasiswa saya') }}
</x-mail::button>

{{ __('Salam,') }}
Skular
</x-mail::message>
