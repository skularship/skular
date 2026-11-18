<?php

namespace App\Filament\Widgets\Analytics;

use App\Contracts\SiteAnalyticsServiceInterface;
use App\Exceptions\AnalyticsUnavailableException;
use App\Filament\Widgets\Analytics\Concerns\ReadsAnalyticsPeriod;
use Filament\Widgets\ChartWidget;

/**
 * Line chart of Google Analytics visitors and page views per day (per hour
 * for "Hari ini"/"Kemarin"). A failed report leaves the chart empty and puts
 * the reason in the description.
 */
class AnalyticsTrendChart extends ChartWidget
{
    use ReadsAnalyticsPeriod;

    protected static bool $isDiscovered = false;

    protected static ?int $sort = 2;

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '280px';

    /**
     * Why the last load failed, shown as the chart description.
     */
    protected ?string $error_message = null;

    /**
     * Chart heading.
     */
    public function getHeading(): string
    {
        return 'Tren kunjungan';
    }

    /**
     * The failure reason, or what the chart shows.
     */
    public function getDescription(): string
    {
        return $this->error_message
            ?? ($this->analyticsPeriod()->isSingleDay() ? 'Pengunjung dan tayangan per jam.' : 'Pengunjung dan tayangan per hari.');
    }

    /**
     * Build the chart datasets from the GA4 trend report.
     *
     * @return array{labels: list<string>, datasets: list<array<string, mixed>>}
     */
    protected function getData(): array
    {
        $this->error_message = null;

        try {
            $points = app(SiteAnalyticsServiceInterface::class)->trend($this->analyticsPeriod());
        } catch (AnalyticsUnavailableException $exception) {
            $this->error_message = $exception->getMessage();
            $points = [];
        }

        return [
            'labels' => array_column($points, 'label'),
            'datasets' => [
                [
                    'label' => 'Pengunjung',
                    'data' => array_column($points, 'users'),
                    'borderColor' => '#f2994a',
                    'backgroundColor' => 'rgba(242, 153, 74, 0.15)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Tayangan halaman',
                    'data' => array_column($points, 'page_views'),
                    'borderColor' => '#277c93',
                    'backgroundColor' => 'rgba(39, 124, 147, 0.1)',
                    'fill' => false,
                    'tension' => 0.3,
                ],
            ],
        ];
    }

    /**
     * Chart.js type.
     */
    protected function getType(): string
    {
        return 'line';
    }
}
