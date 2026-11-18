<?php

namespace App\Filament\Widgets\Analytics;

use App\Contracts\SiteAnalyticsServiceInterface;
use App\Exceptions\AnalyticsUnavailableException;
use App\Filament\Widgets\Analytics\Concerns\ReadsAnalyticsPeriod;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Google Analytics headline numbers (visitors, sessions, page views, average
 * session duration) for the selected period, each compared with the previous
 * period. A failed report shows its reason instead of an error screen.
 */
class AnalyticsOverview extends StatsOverviewWidget
{
    use ReadsAnalyticsPeriod;

    protected static bool $isDiscovered = false;

    protected static ?int $sort = 1;

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    /**
     * Build the four stats, or one stat that explains why data is missing.
     *
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $period = $this->analyticsPeriod();

        try {
            $overview = app(SiteAnalyticsServiceInterface::class)->overview($period);
        } catch (AnalyticsUnavailableException $exception) {
            return [
                Stat::make('Data Google Analytics belum bisa dimuat', '—')
                    ->description($exception->getMessage())
                    ->color('danger'),
            ];
        }

        $current = $overview['current'];
        $previous = $overview['previous'];
        $suffix = $period->comparisonLabel();

        return [
            $this->stat('Pengunjung', $this->number($current['users']), $current['users'], $previous['users'], $suffix),
            $this->stat('Sesi', $this->number($current['sessions']), $current['sessions'], $previous['sessions'], $suffix),
            $this->stat('Tayangan halaman', $this->number($current['page_views']), $current['page_views'], $previous['page_views'], $suffix),
            $this->stat('Rata-rata durasi sesi', $this->duration($current['average_session_seconds']), $current['average_session_seconds'], $previous['average_session_seconds'], $suffix),
        ];
    }

    /**
     * A stat with a trend description against the previous period.
     *
     * @param  string  $label  Stat label.
     * @param  string  $display  Formatted current value.
     * @param  int|float  $current  Current raw value.
     * @param  int|float  $previous  Previous raw value.
     * @param  string  $suffix  Comparison phrase, e.g. "dibanding kemarin".
     */
    private function stat(string $label, string $display, int|float $current, int|float $previous, string $suffix): Stat
    {
        $stat = Stat::make($label, $display);

        if ($previous <= 0) {
            return $stat->description($current > 0 ? 'Belum ada data pembanding' : 'Belum ada kunjungan')->color('gray');
        }

        $change = (int) round(($current - $previous) / $previous * 100);

        return match (true) {
            $change > 0 => $stat->description("Naik {$change}% {$suffix}")
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            $change < 0 => $stat->description('Turun '.abs($change)."% {$suffix}")
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),
            default => $stat->description("Sama {$suffix}")->color('gray'),
        };
    }

    /**
     * Format a count with Indonesian thousands separators.
     */
    private function number(int $value): string
    {
        return number_format($value, 0, ',', '.');
    }

    /**
     * Format seconds as "1 mnt 23 dtk".
     */
    private function duration(float $seconds): string
    {
        $total = (int) round($seconds);
        $minutes = intdiv($total, 60);

        return $minutes > 0 ? sprintf('%d mnt %d dtk', $minutes, $total % 60) : sprintf('%d dtk', $total);
    }
}
