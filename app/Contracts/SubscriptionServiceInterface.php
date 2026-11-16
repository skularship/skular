<?php

namespace App\Contracts;

use App\Models\Product;
use App\Models\Subscription;
use App\Models\User;
use Carbon\CarbonInterface;

/**
 * Grants and extends Pro access.
 */
interface SubscriptionServiceInterface
{
    /**
     * Add the plan's days to the mentee's Pro access.
     *
     * Buying while Pro is still active continues from the current end date, so
     * no day is lost. Call inside a database transaction.
     */
    public function activate(User $user, Product $product, ?CarbonInterface $from = null): Subscription;
}
