<?php

namespace App\Livewire\Onboarding;

use App\Contracts\ProductTourServiceInterface;
use App\Data\TourStepData;
use App\Enums\ProductTour as ProductTourEnum;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Embedded onboarding tour for one page of the mentee app
 * (<livewire:onboarding.product-tour tour="dashboard" />).
 *
 * Opens by itself until the mentee finishes or skips it once; afterwards it
 * only opens through "Ulangi tur" on the Panduan page (?tour=<key>) or the
 * `product-tour:start` browser event. The coach marks run in Alpine
 * (resources/js/product-tour.js); only completion reaches the server.
 */
class ProductTour extends Component
{
    /**
     * Tour key (App\Enums\ProductTour value).
     */
    #[Locked]
    public string $tour;

    /**
     * Validate the tour key.
     *
     * @param  string  $tour  Tour key passed by the page.
     */
    public function mount(string $tour): void
    {
        $this->tour = ProductTourEnum::from($tour)->value;
    }

    /**
     * Remember that the mentee finished or skipped this tour.
     */
    public function complete(ProductTourServiceInterface $tour_service): void
    {
        $user = Auth::user();

        if ($user instanceof User) {
            $tour_service->markCompleted($user, ProductTourEnum::from($this->tour));
        }
    }

    /**
     * Render the tour overlay.
     *
     * @return View The livewire.onboarding.product-tour view.
     */
    public function render(ProductTourServiceInterface $tour_service): View
    {
        $tour = ProductTourEnum::from($this->tour);
        $user = Auth::user();

        return view('livewire.onboarding.product-tour', [
            'key' => $tour->value,
            'steps' => array_map(fn (TourStepData $step): array => $step->toArray(), $tour_service->steps($tour)),
            'auto_start' => $user instanceof User && $tour_service->shouldAutoStart($user, $tour),
        ]);
    }
}
