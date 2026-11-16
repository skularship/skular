<?php

namespace App\Services;

use App\Contracts\SubscriptionServiceInterface;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Stores one Pro subscription row per mentee and moves its end date forward.
 */
class SubscriptionService implements SubscriptionServiceInterface
{
    /**
     * Add the plan's days to the mentee's Pro access.
     *
     * Buying while Pro is still active continues from the current end date, so
     * no day is lost. Call inside a database transaction.
     */
    public function activate(User $user, Product $product, ?CarbonInterface $from = null): Subscription
    {
        $now = CarbonImmutable::instance($from ?? now());

        $subscription = Subscription::query()
            ->where('user_id', $user->id)
            ->lockForUpdate()
            ->first() ?? new Subscription(['user_id' => $user->id]);

        $base = $subscription->exists && $subscription->active_until->greaterThan($now)
            ? $subscription->active_until
            : $now;

        $subscription->plan = $product->code;
        $subscription->active_until = $base->addDays($product->duration_days);
        $subscription->save();

        $user->unsetRelation('proSubscription');

        return $subscription;
    }
}
