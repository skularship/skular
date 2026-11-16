<?php

namespace App\Console\Commands;

use App\Jobs\ProcessMidtransNotification;
use App\Models\PaymentEvent;
use Illuminate\Console\Command;

/**
 * Safety net: queue again any stored Midtrans notification that was never applied.
 */
class RequeueStuckPayments extends Command
{
    /**
     * @var string
     */
    protected $signature = 'payments:requeue-stuck {--minutes=5 : Only events older than this}';

    /**
     * @var string
     */
    protected $description = 'Queue again Midtrans notifications that were stored but not processed';

    /**
     * Find unprocessed events older than the threshold and dispatch them again.
     */
    public function handle(): int
    {
        $count = 0;

        PaymentEvent::query()
            ->whereNull('processed_at')
            ->where('created_at', '<=', now()->subMinutes((int) $this->option('minutes')))
            ->each(function (PaymentEvent $event) use (&$count): void {
                ProcessMidtransNotification::dispatch($event->id);
                $count++;
            });

        $this->info("{$count} notifikasi diantrekan ulang.");

        return self::SUCCESS;
    }
}
