<?php

use App\Jobs\SendProExpiryReminder;
use App\Mail\ProExpiryMail;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    config(['pricing.enabled' => true]);
});

/**
 * Create a Pro subscription that ends in the given number of days.
 */
function subscriptionEndingIn(int $days): Subscription
{
    return Subscription::query()->create([
        'user_id' => User::factory()->create()->id,
        'plan' => 'pro_12bln',
        'active_until' => now()->addDays($days)->setTime(12, 0),
    ]);
}

test('the daily command queues reminders only for plans ending in 7 or 1 day', function () {
    Queue::fake();
    subscriptionEndingIn(7);
    subscriptionEndingIn(1);
    subscriptionEndingIn(4);

    $this->artisan('subscriptions:remind-expiry')->assertSuccessful();

    Queue::assertPushed(SendProExpiryReminder::class, 2);
    Queue::assertPushedOn('reminders', SendProExpiryReminder::class);
});

test('each end date is reminded once, and a renewal reminds again', function () {
    Mail::fake();
    $subscription = subscriptionEndingIn(7);

    SendProExpiryReminder::dispatchSync($subscription->id, 7);
    SendProExpiryReminder::dispatchSync($subscription->id, 7);
    Mail::assertQueued(ProExpiryMail::class, 1);

    $subscription->update(['active_until' => $subscription->active_until->addDays(365)]);
    $subscription->update(['active_until' => now()->addDays(7)->setTime(13, 0)]);

    SendProExpiryReminder::dispatchSync($subscription->id, 7);
    Mail::assertQueued(ProExpiryMail::class, 2);
});

test('no reminder is sent once Pro has already ended', function () {
    Mail::fake();
    $subscription = subscriptionEndingIn(-1);

    SendProExpiryReminder::dispatchSync($subscription->id, 1);

    Mail::assertNothingQueued();
});
