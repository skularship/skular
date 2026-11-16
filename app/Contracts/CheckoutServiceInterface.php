<?php

namespace App\Contracts;

use App\Models\Order;
use App\Models\User;

/**
 * Turns a product choice into a pending order with a Snap token.
 */
interface CheckoutServiceInterface
{
    /**
     * Create (or reuse a recent identical) pending order and its Snap token.
     *
     * The price is always recalculated here; the browser only sends the product code and an optional referral code.
     *
     * @throws \App\Exceptions\PaymentException When the plan is not for sale, the referral code is refused, or Midtrans cannot start the payment.
     */
    public function start(User $user, string $product_code, ?string $promo_code = null): Order;
}
