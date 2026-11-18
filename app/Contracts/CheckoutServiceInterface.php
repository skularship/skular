<?php

namespace App\Contracts;

use App\Exceptions\PaymentException;
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
     * Pass $reuse_pending = false after Snap reported an error, so the next try gets a fresh Snap session.
     *
     * @throws PaymentException When the plan is not for sale, the referral code is refused, or Midtrans cannot start the payment.
     */
    public function start(User $user, string $product_code, ?string $promo_code = null, bool $reuse_pending = true): Order;
}
