<?php

namespace App\Jobs;

use App\Mail\ProExpiryMail;
use App\Models\Subscription;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Sends the "Pro ends in 7 days" or "in 1 day" email once per end date.
 *
 * The end date is remembered on the subscription, so renewing (a new end date)
 * makes the next cycle remind again without any cleanup.
 */
class SendProExpiryReminder implements ShouldQueue
{
    use Queueable;

    /**
     * Attempts before the job is parked in failed_jobs.
     */
    public int $tries = 3;

    /**
     * Keep the subscription id and the offset (7 or 1), and use the reminders queue.
     */
    public function __construct(public readonly int $subscriptionId, public readonly int $daysBefore)
    {
        $this->onQueue('reminders');
    }

    /**
     * Send the email unless Pro already ended or this end date was already reminded.
     */
    public function handle(): void
    {
        $column = $this->daysBefore === 1 ? 'reminded_1_for' : 'reminded_7_for';

        $subscription = DB::transaction(function () use ($column): ?Subscription {
            $subscription = Subscription::query()->with('user')->lockForUpdate()->find($this->subscriptionId);

            if (! $subscription instanceof Subscription
                || $subscription->active_until->isPast()
                || $subscription->{$column}?->equalTo($subscription->active_until)) {
                return null;
            }

            $subscription->{$column} = $subscription->active_until;
            $subscription->save();

            return $subscription;
        });

        if ($subscription instanceof Subscription) {
            Mail::to($subscription->user)->send(new ProExpiryMail($subscription, $this->daysBefore));
        }
    }
}
