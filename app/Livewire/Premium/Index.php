<?php

namespace App\Livewire\Premium;

use App\Contracts\CheckoutServiceInterface;
use App\Contracts\ConsentServiceInterface;
use App\Contracts\PaymentGatewayInterface;
use App\Contracts\PricingServiceInterface;
use App\Contracts\ReferralServiceInterface;
use App\Enums\ConsentSource;
use App\Enums\LegalDocument;
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

    #[Url(as: 'kode', except: '')]
    public string $promo = '';

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
     *
     * Paying means accepting the Terms and the Refund Policy shown next to the
     * button, so a started order logs that consent (source checkout).
     */
    public function checkout(CheckoutServiceInterface $checkout, PricingServiceInterface $pricing, ConsentServiceInterface $consents): void
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
            $order = $checkout->start($user, $this->selected, $this->promo);
        } catch (PaymentException $exception) {
            $this->error = $exception->getMessage();

            return;
        }

        $consents->record($user, [LegalDocument::Terms, LegalDocument::Refund], ConsentSource::Checkout);

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
    public function render(PricingServiceInterface $pricing, PaymentGatewayInterface $gateway, ReferralServiceInterface $referrals): View
    {
        $user = Auth::user();
        $promo_discount = 0;
        $promo_error = null;

        if ($user !== null && trim($this->promo) !== '') {
            $quote = $pricing->quote($this->selected);

            try {
                $promo_discount = $quote === null ? 0 : $referrals->apply($user, $this->promo, $quote)['discount'];
            } catch (PaymentException $exception) {
                $promo_error = $exception->getMessage();
            }
        }

        return view('livewire.premium.index', [
            'quotes' => $pricing->quotes(),
            'snap' => $gateway->clientConfig(),
            'promo_discount' => $promo_discount,
            'promo_error' => $promo_error,
            'has_pro' => $user?->hasPro() ?? false,
            'pro_until' => $user?->proSubscription?->active_until,
        ]);
    }
}
