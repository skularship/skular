<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Models\PaymentEvent;
use App\Models\Subscription;
use App\Support\Rupiah;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Admin dashboard payment numbers: revenue and paid orders in the last 30 days,
 * active Pro mentees, and notifications that were stored but not processed.
 */
class PaymentStats extends StatsOverviewWidget
{
    protected static ?int $sort = 3;

    /**
     * Only admins who may read orders see money figures, and only while Premium is on.
     */
    public static function canView(): bool
    {
        return (bool) config('pricing.enabled') && OrderResource::canViewAny();
    }

    /**
     * Build the payment metrics from real database counts.
     *
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $paid = Order::query()
            ->where('status', OrderStatus::Paid)
            ->where('paid_at', '>=', now()->subDays(30));

        $revenue = (int) (clone $paid)->sum('amount');
        $count = (clone $paid)->count();
        $active_pro = Subscription::query()->where('active_until', '>', now())->count();
        $stuck = PaymentEvent::query()->whereNull('processed_at')->where('created_at', '<=', now()->subMinutes(10))->count();

        return [
            Stat::make('Pendapatan 30 hari', Rupiah::format($revenue))
                ->description('Sebelum biaya Midtrans')
                ->color('success'),
            Stat::make('Pesanan lunas 30 hari', (string) $count),
            Stat::make('Pro aktif', (string) $active_pro),
            Stat::make('Notifikasi tertahan', (string) $stuck)
                ->description($stuck > 0 ? 'Cek worker antrean atau antrekan ulang' : 'Semua terproses')
                ->color($stuck > 0 ? 'danger' : 'success'),
        ];
    }
}
