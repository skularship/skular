<?php

namespace App\Contracts;

use App\Data\PriceQuote;
use App\Exceptions\PaymentException;
use App\Models\ReferralCode;
use App\Models\User;

/**
 * Referral codes: one per mentee, 10% off the 12 month plan while launching pricing runs.
 */
interface ReferralServiceInterface
{
    /**
     * Get the mentee's own code, creating it the first time.
     */
    public function codeFor(User $user): ReferralCode;

    /**
     * Check a code typed by a buyer and work out the discount for the given price.
     *
     * @return array{code: ReferralCode, discount: int}
     *
     * @throws PaymentException With a message safe to show when the code cannot be used.
     */
    public function apply(User $buyer, string $code, PriceQuote $quote): array;
}
