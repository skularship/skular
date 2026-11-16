<?php

namespace App\Livewire\Premium;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * "Paket dan Tagihan": the mentee's current Pro plan, end date and transaction history.
 */
#[Title('Paket dan Tagihan')]
class Billing extends Component
{
    /**
     * Hide the page while Premium is switched off.
     */
    public function mount(): void
    {
        abort_unless(config('pricing.enabled'), 404);
    }

    /**
     * Render the plan and the latest transactions.
     *
     * @return View The livewire.premium.billing view.
     */
    public function render(): View
    {
        $user = Auth::user();

        return view('livewire.premium.billing', [
            'has_pro' => $user->hasPro(),
            'subscription' => $user->proSubscription,
            'orders' => $user->orders()->with('product')->latest('id')->limit(20)->get(),
        ]);
    }
}
