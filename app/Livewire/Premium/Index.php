<?php

namespace App\Livewire\Premium;

use App\Contracts\CheckoutServiceInterface;
use App\Contracts\PaymentGatewayInterface;
use App\Contracts\PricingServiceInterface;
use App\Exceptions\PaymentException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * The public Premium page (/premium): price card, what Pro gives, Free vs Pro and FAQ.
 *
 * Visitors can read everything; paying needs an account. The browser only sends
 * the plan code, the price is recalculated on the server, and Pro is activated
 * later by the Midtrans webhook, never by this page.
 */
#[Title('Harga Pro Skular')]
#[Layout('layouts.public')]
class Index extends Component
{
    #[Url(as: 'paket', except: 'pro_12bln')]
    public string $selected = 'pro_12bln';

    public ?string $error = null;

    /**
     * Hide the page completely while Premium is switched off.
     */
    public function mount(): void
    {
        abort_unless(config('pricing.enabled'), 404);
    }

    /**
     * Choose a plan on the price card.
     */
    public function select(string $code, PricingServiceInterface $pricing): void
    {
        if ($pricing->quote($code) !== null) {
            $this->selected = $code;
            $this->error = null;
        }
    }

    /**
     * Start the payment for the selected plan, or send a visitor to register first.
     */
    public function checkout(CheckoutServiceInterface $checkout, PricingServiceInterface $pricing): void
    {
        $this->error = null;

        if ($pricing->quote($this->selected) === null) {
            $this->selected = (string) config('pricing.default_plan');
        }

        $user = Auth::user();

        if ($user === null) {
            session()->put('url.intended', route('premium', ['paket' => $this->selected]));
            $this->redirect(route('register'));

            return;
        }

        if (! $user->hasVerifiedEmail()) {
            $this->redirect(route('verification.notice'));

            return;
        }

        try {
            $order = $checkout->start($user, $this->selected);
        } catch (PaymentException $exception) {
            $this->error = $exception->getMessage();

            return;
        }

        $this->dispatch(
            'open-snap',
            token: $order->snap_token,
            finish_url: route('payment.status', $order),
        );
    }

    /**
     * Render the Premium page.
     *
     * @return View The livewire.premium.index view.
     */
    public function render(PricingServiceInterface $pricing, PaymentGatewayInterface $gateway): View
    {
        $user = Auth::user();

        return view('livewire.premium.index', [
            'quotes' => $pricing->quotes(),
            'snap' => $gateway->clientConfig(),
            'has_pro' => $user?->hasPro() ?? false,
            'pro_until' => $user?->proSubscription?->active_until,
        ]);
    }
}
