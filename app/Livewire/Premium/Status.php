<?php

namespace App\Livewire\Premium;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Payment status page: waiting, paid or failed. It only reads the order; Pro
 * becomes active when the Midtrans webhook marks the order paid, so a waiting
 * order refreshes itself every few seconds.
 */
#[Title('Status Pembayaran')]
class Status extends Component
{
    public Order $order;

    /**
     * Load the order, which must belong to the signed-in mentee.
     */
    public function mount(Order $order): void
    {
        abort_unless(config('pricing.enabled'), 404);
        abort_unless($order->user_id === Auth::id(), 404);

        $this->order = $order;
    }

    /**
     * Render the status of the order.
     *
     * @return View The livewire.premium.status view.
     */
    public function render(): View
    {
        $this->order->refresh()->loadMissing('product');

        return view('livewire.premium.status', [
            'order' => $this->order,
            'is_pending' => $this->order->status === OrderStatus::Pending,
            'pro_until' => Auth::user()?->proSubscription()->first()?->active_until,
        ]);
    }
}
