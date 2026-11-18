<?php

namespace App\Console\Commands;

use App\Contracts\PaymentWebhookServiceInterface;
use App\Enums\OrderStatus;
use App\Exceptions\PaymentException;
use App\Models\Order;
use App\Services\PaymentWebhookService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Safety net for orders left pending: a mentee who closes Snap without paying gets no
 * Midtrans notification, so the order would wait forever. Asks Midtrans about each old
 * pending order, then expires it or queues its final status like a notification.
 */
class ReconcilePendingPayments extends Command
{
    /**
     * @var string
     */
    protected $signature = 'payments:reconcile-pending {--limit=100 : Maximum orders checked in one run}';

    /**
     * @var string
     */
    protected $description = 'Check old pending orders with Midtrans and expire the ones never paid';

    /**
     * Reconcile the oldest pending orders past the Snap expiry.
     *
     * @return int SUCCESS, or FAILURE when Midtrans could not answer for at least one order.
     */
    public function handle(PaymentWebhookServiceInterface $webhook): int
    {
        $counts = ['expired' => 0, 'queued' => 0, 'waiting' => 0, 'error' => 0];

        Order::query()
            ->where('status', OrderStatus::Pending)
            ->where('created_at', '<=', now()->subHours(PaymentWebhookService::EXPIRE_AFTER_HOURS))
            ->oldest('id')
            ->limit(max(1, (int) $this->option('limit')))
            ->get()
            ->each(function (Order $order) use ($webhook, &$counts): void {
                try {
                    $counts[$webhook->reconcile($order)]++;
                } catch (PaymentException $exception) {
                    Log::warning('Pending order could not be reconciled.', ['order_id' => $order->order_id, 'reason' => $exception->getMessage()]);
                    $counts['error']++;
                }
            });

        $this->info("{$counts['expired']} kedaluwarsa, {$counts['queued']} diantrekan, {$counts['waiting']} masih menunggu, {$counts['error']} gagal dicek.");

        return $counts['error'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
