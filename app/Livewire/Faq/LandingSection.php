<?php

namespace App\Livewire\Faq;

use App\Models\Faq;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * The compact FAQ block on the landing page.
 *
 * Only published FAQs the admin marked as featured are considered, and the
 * database limits the result to five before anything reaches the browser.
 */
class LandingSection extends Component
{
    /**
     * Render the FAQ section embedded on the landing page.
     *
     * @return View The livewire.faq.landing-section view.
     */
    public function render(): View
    {
        $faqs = Faq::query()
            ->published()
            ->featured()
            ->with('category')
            ->ordered()
            ->limit(5)
            ->get();

        return view('livewire.faq.landing-section', [
            'faqs' => $faqs,
        ]);
    }
}
