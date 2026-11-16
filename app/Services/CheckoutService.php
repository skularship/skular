<?php

namespace App\Services;

use App\Contracts\CheckoutServiceInterface;
use App\Contracts\PaymentGatewayInterface;
use App\Contracts\PricingServiceInterface;
use App\Contracts\ReferralServiceInterface;
use App\Enums\OrderStatus;
use App\Exceptions\PaymentException;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Creates the pending order for a Premium purchase and asks Midtrans for a Snap token.
 */
class CheckoutService implements CheckoutServiceInterface
{
    /**
     * Minutes a pending order with a token can be reused instead of creating a new one.
     */
    public const REUSE_MINUTES = 20;

    /**
     * Inject the pricing source and the payment provider.
     */
    public function __construct(
        private readonly PricingServiceInterface $pricing,
        private readonly PaymentGatewayInterface $gateway,
        private readonly ReferralServiceInterface $referrals,
    ) {}

    /**
     * Create (or reuse a recent identical) pending order and its Snap token.
     *
     * The price is always recalculated here; the browser only sends the product code and an optional referral code.
     *
     * @throws PaymentException When the plan is not for sale or Midtrans cannot start the payment.
     */
    public function start(User $user, string $product_code, ?string $promo_code = null): Order
    {
        $product = $this->pricing->product($product_code);
        $quote = $this->pricing->quote($product_code);

        if ($product === null || $quote === null) {
            throw PaymentException::productUnavailable();
        }

        $amount = $quote->amount;
        $discount = 0;
        $referral = null;

        if ($promo_code !== null && trim($promo_code) !== '') {
            ['code' => $referral, 'discount' => $discount] = $this->referrals->apply($user, $promo_code, $quote);
            $amount -= $discount;
        }

        $reusable = Order::query()
            ->where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->where('amount', $amount)
            ->where('promo_code', $referral?->code)
            ->where('status', OrderStatus::Pending)
            ->whereNotNull('snap_token')
            ->where('created_at', '>=', now()->subMinutes(self::REUSE_MINUTES))
            ->latest('id')
            ->first();

        if ($reusable instanceof Order) {
            return $reusable;
        }

        $order = Order::query()->create([
            'order_id' => 'SKL-'.now()->format('ymd').'-'.Str::upper(Str::random(8)),
            'user_id' => $user->id,
            'product_id' => $product->id,
            'amount' => $amount,
            'promo_code' => $referral?->code,
            'referrer_user_id' => $referral?->user_id,
            'discount_amount' => $discount,
            'status' => OrderStatus::Pending,
        ]);

        try {
            $order->snap_token = $this->gateway->createSnapToken($order, $user, $product);
            $order->save();
        } catch (PaymentException $exception) {
            $order->status = OrderStatus::Failed;
            $order->save();

            throw $exception;
        }

        return $order;
    }
}
