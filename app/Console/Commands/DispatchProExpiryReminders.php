<?php

namespace App\Console\Commands;

use App\Jobs\SendProExpiryReminder;
use App\Models\Subscription;
use Illuminate\Console\Command;

/**
 * Runs daily: queues the "Pro ends soon" email for plans ending in 7 days or in 1 day.
 */
class DispatchProExpiryReminders extends Command
{
    /**
     * @var string
     */
    protected $signature = 'subscriptions:remind-expiry';

    /**
     * @var string
     */
    protected $description = 'Queue Pro expiry reminder emails (7 days and 1 day before)';

    /**
     * Find subscriptions ending on each target date and dispatch their jobs.
     */
    public function handle(): int
    {
        if (! config('pricing.enabled')) {
            return self::SUCCESS;
        }

        $count = 0;

        foreach ([7, 1] as $days) {
            Subscription::query()
                ->whereDate('active_until', now()->addDays($days)->toDateString())
                ->each(function (Subscription $subscription) use ($days, &$count): void {
                    SendProExpiryReminder::dispatch($subscription->id, $days);
                    $count++;
                });
        }

        $this->info("{$count} pengingat Pro diantrekan.");

        return self::SUCCESS;
    }
}
