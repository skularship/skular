{{-- Onboarding tour of one mentee page (App\Livewire\Onboarding\ProductTour). Completion is stored on the server through $wire.complete(). --}}
<div>
    <x-onboarding.tour-overlay :key="$key" :steps="$steps" :auto-start="$auto_start" persist="server" />
</div>
