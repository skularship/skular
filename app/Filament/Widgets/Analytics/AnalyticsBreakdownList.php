<?php

namespace App\Filament\Widgets\Analytics;

use App\Contracts\SiteAnalyticsServiceInterface;
use App\Enums\AnalyticsBreakdown;
use App\Exceptions\AnalyticsUnavailableException;
use App\Filament\Widgets\Analytics\Concerns\ReadsAnalyticsPeriod;
use Filament\Widgets\Widget;

/**
 * Top-10 Google Analytics list (pages, sources, devices or countries) for the
 * selected period. One class serves every list through the `breakdown`
 * property passed with `AnalyticsBreakdownList::make(['breakdown' => ...])`.
 */
class AnalyticsBreakdownList extends Widget
{
    use ReadsAnalyticsPeriod;

    protected static bool $isDiscovered = false;

    protected static ?int $sort = 3;

    protected string $view = 'filament.widgets.analytics-breakdown-list';

    /**
     * Value of App\Enums\AnalyticsBreakdown to show.
     */
    public string $breakdown = AnalyticsBreakdown::Pages->value;

    /**
     * Data for the list view.
     *
     * @return array{type: AnalyticsBreakdown, rows: list<array{label: string, value: int, percentage: int}>, error: string|null}
     */
    protected function getViewData(): array
    {
        $breakdown = AnalyticsBreakdown::tryFrom($this->breakdown) ?? AnalyticsBreakdown::Pages;

        try {
            $rows = app(SiteAnalyticsServiceInterface::class)->breakdown($this->analyticsPeriod(), $breakdown);
            $error = null;
        } catch (AnalyticsUnavailableException $exception) {
            $rows = [];
            $error = $exception->getMessage();
        }

        $max = max([1, ...array_column($rows, 'value')]);

        return [
            'type' => $breakdown,
            'rows' => array_map(fn (array $row): array => [
                ...$row,
                'percentage' => (int) round($row['value'] / $max * 100),
            ], $rows),
            'error' => $error,
        ];
    }
}
