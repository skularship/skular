<?php

namespace App\Filament\Pages;

use App\Contracts\SiteAnalyticsServiceInterface;
use App\Enums\AnalyticsBreakdown;
use App\Enums\AnalyticsFailureReason;
use App\Enums\AnalyticsPeriod;
use App\Filament\Widgets\Analytics\AnalyticsBreakdownList;
use App\Filament\Widgets\Analytics\AnalyticsOverview;
use App\Filament\Widgets\Analytics\AnalyticsTrendChart;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\WidgetConfiguration;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Website traffic from Google Analytics 4 (`/admin/google-analytics-dashboard`).
 *
 * Replaces the filament-google-analytics plugin page: one period filter for
 * the whole page, one cached and time-limited GA4 call per widget, and a
 * readable reason (missing setup, no access, timeout) instead of an error
 * screen when data cannot be loaded.
 */
class SiteAnalytics extends BaseDashboard
{
    use HasFiltersForm;

    protected static string $routePath = '/google-analytics-dashboard';

    protected static ?string $slug = 'google-analytics-dashboard';

    protected static ?string $title = 'Google Analytics';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?int $navigationSort = -1;

    /**
     * Page subheading.
     */
    public function getSubheading(): string|Htmlable|null
    {
        return 'Trafik situs publik dari Google Analytics 4. Angka hari ini diperbarui tiap 5 menit, hari sebelumnya tiap jam.';
    }

    /**
     * The period filter shared by every widget.
     *
     * @param  Schema  $schema  The filters form schema.
     * @return Schema The configured schema.
     */
    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('period')
                ->label('Periode')
                ->options(AnalyticsPeriod::options())
                ->default(AnalyticsPeriod::Last7Days->value)
                ->selectablePlaceholder(false)
                ->native(false),
        ]);
    }

    /**
     * Show the setup instructions instead of widgets while GA4 is not configured.
     *
     * @param  Schema  $schema  The page schema.
     * @return Schema The configured schema.
     */
    public function content(Schema $schema): Schema
    {
        if (! app(SiteAnalyticsServiceInterface::class)->isConfigured()) {
            return $schema->components([
                Callout::make('Google Analytics belum dihubungkan')
                    ->description(AnalyticsFailureReason::NotConfigured->message()
                        .' File kredensial disimpan di storage/app/analytics/service-account-credentials.json.')
                    ->warning(),
            ]);
        }

        return parent::content($schema);
    }

    /**
     * Widgets of this page only (they are excluded from auto-discovery).
     *
     * @return array<class-string|WidgetConfiguration>
     */
    public function getWidgets(): array
    {
        return [
            AnalyticsOverview::class,
            AnalyticsTrendChart::class,
            ...array_map(
                fn (AnalyticsBreakdown $breakdown): WidgetConfiguration => AnalyticsBreakdownList::make(['breakdown' => $breakdown->value]),
                AnalyticsBreakdown::cases(),
            ),
        ];
    }
}
