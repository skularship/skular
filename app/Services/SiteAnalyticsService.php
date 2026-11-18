<?php

namespace App\Services;

use App\Contracts\SiteAnalyticsServiceInterface;
use App\Enums\AnalyticsBreakdown;
use App\Enums\AnalyticsFailureReason;
use App\Enums\AnalyticsPeriod;
use App\Exceptions\AnalyticsUnavailableException;
use Closure;
use Google\Analytics\Data\V1beta\Client\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Dimension;
use Google\Analytics\Data\V1beta\Metric;
use Google\Analytics\Data\V1beta\OrderBy;
use Google\Analytics\Data\V1beta\OrderBy\DimensionOrderBy;
use Google\Analytics\Data\V1beta\OrderBy\MetricOrderBy;
use Google\Analytics\Data\V1beta\Row;
use Google\Analytics\Data\V1beta\RunReportRequest;
use Google\Analytics\Data\V1beta\RunReportResponse;
use Google\ApiCore\ApiException;
use Google\Auth\Cache\FileSystemCacheItemPool;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * GA4 Data API reader behind the admin analytics page.
 *
 * Each widget needs exactly one runReport call for the selected period (the
 * comparison period is a second date range in the same call). Calls are
 * time-limited without retries so a slow API never hangs the admin page,
 * results are cached as plain arrays, and failures are not cached.
 */
class SiteAnalyticsService implements SiteAnalyticsServiceInterface
{
    /**
     * Upper bound for one GA4 call; the widget shows a message instead of waiting longer.
     */
    public const TIMEOUT_MILLIS = 10_000;

    /**
     * Lazily created API client (only on a cache miss).
     */
    private ?BetaAnalyticsDataClient $client = null;

    /**
     * {@inheritDoc}
     */
    public function isConfigured(): bool
    {
        $credentials = config('analytics.service_account_credentials_json');

        if (blank(config('analytics.property_id'))) {
            return false;
        }

        return is_array($credentials)
            ? filled($credentials)
            : is_string($credentials) && is_file($credentials);
    }

    /**
     * {@inheritDoc}
     */
    public function serviceAccountEmail(): ?string
    {
        $credentials = config('analytics.service_account_credentials_json');

        if (is_string($credentials) && is_file($credentials)) {
            $credentials = json_decode((string) file_get_contents($credentials), true);
        }

        $email = is_array($credentials) ? ($credentials['client_email'] ?? null) : null;

        return is_string($email) && $email !== '' ? $email : null;
    }

    /**
     * {@inheritDoc}
     */
    public function overview(AnalyticsPeriod $period): array
    {
        return $this->remember('overview', $period, function () use ($period): array {
            $response = $this->runReport(new RunReportRequest([
                'property' => $this->property(),
                'date_ranges' => [
                    $this->dateRange($period->currentRange(), 'current'),
                    $this->dateRange($period->previousRange(), 'previous'),
                ],
                'metrics' => $this->metrics(['activeUsers', 'sessions', 'screenPageViews', 'averageSessionDuration']),
            ]));

            $empty = ['users' => 0, 'sessions' => 0, 'page_views' => 0, 'average_session_seconds' => 0.0];
            $totals = ['current' => $empty, 'previous' => $empty];
            $range_index = $this->dimensionIndex($response, 'dateRange');

            /** @var Row $row */
            foreach ($response->getRows() as $position => $row) {
                // GA4 names each row after its date range; fall back to request order.
                $name = $range_index !== null
                    ? $row->getDimensionValues()[$range_index]->getValue()
                    : ($position === 0 ? 'current' : 'previous');

                if (! isset($totals[$name])) {
                    continue;
                }

                $metrics = $row->getMetricValues();
                $totals[$name] = [
                    'users' => (int) $metrics[0]->getValue(),
                    'sessions' => (int) $metrics[1]->getValue(),
                    'page_views' => (int) $metrics[2]->getValue(),
                    'average_session_seconds' => round((float) $metrics[3]->getValue(), 1),
                ];
            }

            return $totals;
        });
    }

    /**
     * {@inheritDoc}
     */
    public function trend(AnalyticsPeriod $period): array
    {
        $dimension = $period->isSingleDay() ? 'hour' : 'date';

        return $this->remember('trend', $period, function () use ($period, $dimension): array {
            $response = $this->runReport(new RunReportRequest([
                'property' => $this->property(),
                'date_ranges' => [$this->dateRange($period->currentRange())],
                'dimensions' => [new Dimension(['name' => $dimension])],
                'metrics' => $this->metrics(['activeUsers', 'screenPageViews']),
                'order_bys' => [new OrderBy(['dimension' => new DimensionOrderBy(['dimension_name' => $dimension])])],
                'limit' => 200,
            ]));

            $points = [];

            /** @var Row $row */
            foreach ($response->getRows() as $row) {
                $value = $row->getDimensionValues()[0]->getValue();
                $metrics = $row->getMetricValues();

                $points[] = [
                    'label' => $dimension === 'hour'
                        ? sprintf('%02d.00', (int) $value)
                        : Carbon::createFromFormat('Ymd', $value)?->translatedFormat('j M') ?? $value,
                    'users' => (int) $metrics[0]->getValue(),
                    'page_views' => (int) $metrics[1]->getValue(),
                ];
            }

            return $points;
        });
    }

    /**
     * {@inheritDoc}
     */
    public function breakdown(AnalyticsPeriod $period, AnalyticsBreakdown $breakdown): array
    {
        return $this->remember('breakdown:'.$breakdown->value, $period, function () use ($period, $breakdown): array {
            $response = $this->runReport(new RunReportRequest([
                'property' => $this->property(),
                'date_ranges' => [$this->dateRange($period->currentRange())],
                'dimensions' => [new Dimension(['name' => $breakdown->dimension()])],
                'metrics' => $this->metrics([$breakdown->metric()]),
                'order_bys' => [new OrderBy([
                    'metric' => new MetricOrderBy(['metric_name' => $breakdown->metric()]),
                    'desc' => true,
                ])],
                'limit' => 10,
            ]));

            $rows = [];

            /** @var Row $row */
            foreach ($response->getRows() as $row) {
                $rows[] = [
                    'label' => $breakdown->displayLabel($row->getDimensionValues()[0]->getValue()),
                    'value' => (int) $row->getMetricValues()[0]->getValue(),
                ];
            }

            return $rows;
        });
    }

    /**
     * Run one GA4 report with a hard timeout and no retries.
     *
     * Kept as a separate method so tests can replace the network call.
     *
     * @param  RunReportRequest  $request  The prepared report request.
     * @return RunReportResponse The API response.
     *
     * @throws ApiException When the API rejects or fails the call.
     */
    protected function runReport(RunReportRequest $request): RunReportResponse
    {
        return $this->client()->runReport($request, ['timeoutMillis' => self::TIMEOUT_MILLIS]);
    }

    /**
     * Serve a report from cache, or build it and cache the plain-array result.
     *
     * @template TResult of array
     *
     * @param  string  $report  Report name used in the cache key.
     * @param  AnalyticsPeriod  $period  The reporting period.
     * @param  Closure(): TResult  $build  Builds the report from the API.
     * @return TResult
     *
     * @throws AnalyticsUnavailableException When GA4 is not configured or the call fails.
     */
    private function remember(string $report, AnalyticsPeriod $period, Closure $build): array
    {
        if (! $this->isConfigured()) {
            throw new AnalyticsUnavailableException(
                AnalyticsFailureReason::NotConfigured,
                AnalyticsFailureReason::NotConfigured->message(),
            );
        }

        // The date is in the key so "today" and relative ranges roll over at midnight.
        $key = sprintf(
            'site-analytics:%s:%s:%s:%s',
            md5((string) config('analytics.property_id')),
            $report,
            $period->value,
            now()->toDateString(),
        );

        try {
            return Cache::remember($key, $period->cacheSeconds(), $build);
        } catch (Throwable $exception) {
            $reason = AnalyticsFailureReason::fromThrowable($exception);

            Log::warning('Google Analytics report failed.', [
                'reason' => $reason->value,
                'report' => $report,
                'period' => $period->value,
                'status' => $exception instanceof ApiException ? $exception->getStatus() : null,
            ]);

            throw new AnalyticsUnavailableException($reason, $reason->message($this->serviceAccountEmail()), $exception);
        }
    }

    /**
     * Build (once) the GA4 client from the spatie/laravel-analytics config.
     *
     * The OAuth access token is cached on disk so a new request does not
     * fetch a fresh token from Google before every report.
     */
    private function client(): BetaAnalyticsDataClient
    {
        return $this->client ??= new BetaAnalyticsDataClient([
            'credentials' => config('analytics.service_account_credentials_json'),
            'credentialsConfig' => [
                'authCache' => new FileSystemCacheItemPool(storage_path('framework/cache/google-auth')),
            ],
            'disableRetries' => true,
        ]);
    }

    /**
     * The resource name of the configured property.
     */
    private function property(): string
    {
        return 'properties/'.config('analytics.property_id');
    }

    /**
     * @param  array{start: string, end: string}  $range  GA4 relative or absolute dates.
     * @param  string|null  $name  Name echoed back in the `dateRange` dimension.
     */
    private function dateRange(array $range, ?string $name = null): DateRange
    {
        return new DateRange(array_filter([
            'start_date' => $range['start'],
            'end_date' => $range['end'],
            'name' => $name,
        ]));
    }

    /**
     * @param  list<string>  $names  GA4 metric names.
     * @return list<Metric>
     */
    private function metrics(array $names): array
    {
        return array_map(fn (string $name): Metric => new Metric(['name' => $name]), $names);
    }

    /**
     * Position of a dimension header in the response, or null when absent.
     */
    private function dimensionIndex(RunReportResponse $response, string $name): ?int
    {
        foreach ($response->getDimensionHeaders() as $index => $header) {
            if ($header->getName() === $name) {
                return (int) $index;
            }
        }

        return null;
    }
}
