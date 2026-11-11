{{--
    Landing page product tour for visitors (route home). Steps come from ProductTourService (ProductTour::Landing);
    completion is remembered in localStorage because visitors have no account yet. "Ulangi tur" on /panduan links to /?tour=landing.
--}}
@php
    $landing_tour_steps = array_map(
        fn (\App\Data\TourStepData $step): array => $step->toArray(),
        app(\App\Contracts\ProductTourServiceInterface::class)->steps(\App\Enums\ProductTour::Landing),
    );
@endphp

<x-onboarding.tour-overlay key="landing" :steps="$landing_tour_steps" :auto-start="! auth()->check()" persist="local" />
