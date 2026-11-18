<?php

namespace App\Filament\Widgets\Analytics\Concerns;

use App\Enums\AnalyticsPeriod;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

/**
 * Reads the period chosen in the filter of the admin Google Analytics page.
 *
 * The widgets are not auto-discovered (`$isDiscovered = false`), so they only
 * appear on that page and never on the curriculum dashboard.
 */
trait ReadsAnalyticsPeriod
{
    use InteractsWithPageFilters;

    /**
     * The selected period, or the 7-day default for a missing or unknown value.
     */
    protected function analyticsPeriod(): AnalyticsPeriod
    {
        $value = $this->pageFilters['period'] ?? null;

        return AnalyticsPeriod::tryFrom(is_string($value) ? $value : '') ?? AnalyticsPeriod::Last7Days;
    }
}
