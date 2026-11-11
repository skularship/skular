<?php

namespace App\Livewire\Onboarding;

use App\Contracts\ProductTourServiceInterface;
use App\Enums\ProductTour;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * "Panduan" page (route guide.index): the whole Skular journey from landing
 * page to reporting results, with "Ulangi tur" to replay each product tour.
 */
#[Title('Panduan')]
class Guide extends Component
{
    /**
     * Replay one tour: forget it and open the page where it runs.
     *
     * The tour opens there by itself (?tour=<key>). The module tour runs
     * inside a module, so it waits until the mentee opens the next module.
     *
     * @param  string  $tour  ProductTour value.
     */
    public function replay(string $tour, ProductTourServiceInterface $tour_service): void
    {
        $product_tour = ProductTour::tryFrom($tour);

        if ($product_tour === null) {
            return;
        }

        $tour_service->reset($this->user(), $product_tour);

        if ($product_tour === ProductTour::Module) {
            $this->dispatch('toast', variant: 'info', text: __('Panduan modul akan muncul lagi saat kamu membuka modul berikutnya.'));
            $this->redirect(route('roadmap.show'), navigate: true);

            return;
        }

        $this->redirect(route($product_tour->routeName(), ['tour' => $product_tour->value]), navigate: $product_tour !== ProductTour::Landing);
    }

    /**
     * Render the guide page.
     *
     * @return View The livewire.onboarding.guide view.
     */
    public function render(ProductTourServiceInterface $tour_service): View
    {
        $user = $this->user();

        $stages = array_map(fn (ProductTour $tour): array => [
            'tour' => $tour,
            // The landing tour is remembered in the browser, not on the account.
            'seen' => $tour !== ProductTour::Landing && $tour_service->isCompleted($user, $tour),
        ], ProductTour::cases());

        return view('livewire.onboarding.guide', [
            'stages' => $stages,
            'mentor_limit' => (int) config('ai.module_chat.limit', 5),
        ]);
    }

    /**
     * Get the authenticated mentee.
     */
    private function user(): User
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            abort(403);
        }

        return $user;
    }
}
