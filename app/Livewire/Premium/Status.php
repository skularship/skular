<?php

namespace App\Livewire\Premium;

use App\Contracts\PaymentWebhookServiceInterface;
use App\Enums\OrderStatus;
use App\Exceptions\PaymentException;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Payment status page: waiting, paid or failed. It only reads the order; Pro
 * becomes active when the Midtrans webhook marks the order paid, so a waiting
 * order refreshes itself every few seconds. "Cek status pembayaran" asks Midtrans
 * directly for the case where a payment went through but its notification is late.
 */
#[Title('Status Pembayaran')]
class Status extends Component
{
    /**
     * Seconds a mentee waits between two "Cek status pembayaran" clicks on one order.
     */
    public const CHECK_COOLDOWN_SECONDS = 10;

    public Order $order;

    public ?string $notice = null;

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
     * Ask Midtrans about this order now and apply a final status at once.
     *
     * The order changes only through PaymentWebhookService (Midtrans data, amount checked);
     * this button never marks an order paid by itself.
     */
    public function checkStatus(PaymentWebhookServiceInterface $webhook): void
    {
        $this->notice = null;
        $key = 'payment-check:'.$this->order->id;

        if (RateLimiter::tooManyAttempts($key, 1)) {
            $this->notice = __('Tunggu :seconds detik sebelum cek lagi.', ['seconds' => RateLimiter::availableIn($key)]);

            return;
        }

        RateLimiter::hit($key, self::CHECK_COOLDOWN_SECONDS);

        try {
            $status = $webhook->refresh($this->order);
        } catch (PaymentException) {
            $this->notice = __('Status belum bisa dicek sekarang. Coba lagi sebentar lagi atau tanya lewat WhatsApp.');

            return;
        }

        if ($status === OrderStatus::Pending) {
            $this->notice = __('Pembayaranmu belum masuk. Kalau kamu baru saja membayar, tunggu sebentar lalu cek lagi.');
        }
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
