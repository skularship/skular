<?php

use App\Contracts\SiteAnalyticsServiceInterface;
use App\Enums\AdminRole;
use App\Enums\AnalyticsBreakdown;
use App\Enums\AnalyticsFailureReason;
use App\Enums\AnalyticsPeriod;
use App\Exceptions\AnalyticsUnavailableException;
use App\Filament\Widgets\Analytics\AnalyticsBreakdownList;
use App\Filament\Widgets\Analytics\AnalyticsOverview;
use App\Filament\Widgets\Analytics\AnalyticsTrendChart;
use App\Models\User;
use App\Services\SiteAnalyticsService;
use Google\Analytics\Data\V1beta\DimensionHeader;
use Google\Analytics\Data\V1beta\DimensionValue;
use Google\Analytics\Data\V1beta\MetricValue;
use Google\Analytics\Data\V1beta\Row;
use Google\Analytics\Data\V1beta\RunReportRequest;
use Google\Analytics\Data\V1beta\RunReportResponse;
use Google\ApiCore\ApiException;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/**
 * Create a super admin that can open the analytics page.
 */
function analyticsAdmin(): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole(Role::findOrCreate(AdminRole::SuperAdmin->value, 'web'));

    return $user;
}

/**
 * Point the analytics config at a fake property with inline credentials.
 */
function configureAnalytics(): void
{
    config([
        'analytics.property_id' => '123456789',
        'analytics.service_account_credentials_json' => [
            'type' => 'service_account',
            'client_email' => 'skular-ga@example.iam.gserviceaccount.com',
        ],
    ]);
}

/**
 * A SiteAnalyticsService whose GA4 call is replaced by a callback; counts calls.
 *
 * @param  Closure(RunReportRequest): RunReportResponse  $respond
 */
function analyticsServiceReturning(Closure $respond): SiteAnalyticsService
{
    return new class($respond) extends SiteAnalyticsService
    {
        public int $calls = 0;

        public function __construct(private Closure $respond) {}

        protected function runReport(RunReportRequest $request): RunReportResponse
        {
            $this->calls++;

            return ($this->respond)($request);
        }
    };
}

/**
 * Build a GA4 row from dimension and metric strings.
 *
 * @param  list<string>  $dimensions
 * @param  list<string>  $metrics
 */
function gaRow(array $dimensions, array $metrics): Row
{
    return new Row([
        'dimension_values' => array_map(fn (string $value): DimensionValue => new DimensionValue(['value' => $value]), $dimensions),
        'metric_values' => array_map(fn (string $value): MetricValue => new MetricValue(['value' => $value]), $metrics),
    ]);
}

test('the overview needs one GA4 call for both periods and is served from cache afterwards', function () {
    configureAnalytics();

    $service = analyticsServiceReturning(function (RunReportRequest $request): RunReportResponse {
        expect($request->getProperty())->toBe('properties/123456789')
            ->and(count($request->getDateRanges()))->toBe(2);

        return new RunReportResponse([
            'dimension_headers' => [new DimensionHeader(['name' => 'dateRange'])],
            // GA4 does not promise row order, so "previous" comes first here.
            'rows' => [
                gaRow(['previous'], ['80', '100', '300', '45.25']),
                gaRow(['current'], ['120', '150', '420', '61.5']),
            ],
        ]);
    });

    $first = $service->overview(AnalyticsPeriod::Last7Days);
    $second = $service->overview(AnalyticsPeriod::Last7Days);

    expect($first)->toBe($second)
        ->and($first['current'])->toBe(['users' => 120, 'sessions' => 150, 'page_views' => 420, 'average_session_seconds' => 61.5])
        ->and($first['previous']['users'])->toBe(80)
        ->and($service->calls)->toBe(1);
});

test('breakdown rows get readable labels and are ranked by GA4', function () {
    configureAnalytics();

    $service = analyticsServiceReturning(fn (): RunReportResponse => new RunReportResponse([
        'rows' => [
            gaRow(['(direct) / (none)'], ['40']),
            gaRow(['google / organic'], ['25']),
            gaRow(['(not set)'], ['3']),
        ],
    ]));

    expect($service->breakdown(AnalyticsPeriod::Today, AnalyticsBreakdown::Sources))->toBe([
        ['label' => 'Langsung (tanpa rujukan)', 'value' => 40],
        ['label' => 'google / organic', 'value' => 25],
        ['label' => 'Tidak diketahui', 'value' => 3],
    ]);
});

test('a GA4 permission error becomes a readable reason and is not cached', function () {
    configureAnalytics();

    $service = analyticsServiceReturning(function (): RunReportResponse {
        throw new ApiException('User does not have sufficient permissions for this property.', 7, 'PERMISSION_DENIED');
    });

    $attempt = fn () => $service->trend(AnalyticsPeriod::Last28Days);

    expect($attempt)->toThrow(function (AnalyticsUnavailableException $exception): void {
        expect($exception->reason)->toBe(AnalyticsFailureReason::PermissionDenied)
            ->and($exception->getMessage())->toContain('skular-ga@example.iam.gserviceaccount.com');
    });
    expect($attempt)->toThrow(AnalyticsUnavailableException::class);
    expect($service->calls)->toBe(2);
});

test('timeouts and quota errors are classified separately', function (string $status, AnalyticsFailureReason $reason) {
    expect(AnalyticsFailureReason::fromThrowable(new ApiException('failed', 0, $status)))->toBe($reason);
})->with([
    'deadline' => ['DEADLINE_EXCEEDED', AnalyticsFailureReason::Timeout],
    'quota' => ['RESOURCE_EXHAUSTED', AnalyticsFailureReason::RateLimited],
    'wrong property' => ['NOT_FOUND', AnalyticsFailureReason::NotConfigured],
    'outage' => ['UNAVAILABLE', AnalyticsFailureReason::Unavailable],
]);

test('without a property id the page explains the setup and never calls GA4', function () {
    config(['analytics.property_id' => null]);

    $this->actingAs(analyticsAdmin())
        ->get('/admin/google-analytics-dashboard')
        ->assertOk()
        ->assertSee('Google Analytics belum dihubungkan')
        ->assertSee('ANALYTICS_PROPERTY_ID');
});

test('the analytics page loads when GA4 is configured', function () {
    configureAnalytics();

    $this->actingAs(analyticsAdmin())
        ->get('/admin/google-analytics-dashboard')
        ->assertOk()
        ->assertSee('Periode')
        ->assertDontSee('Google Analytics belum dihubungkan');
});

test('widgets show the failure reason instead of crashing', function () {
    $error = new AnalyticsUnavailableException(AnalyticsFailureReason::Timeout, AnalyticsFailureReason::Timeout->message());

    $failing = Mockery::mock(SiteAnalyticsServiceInterface::class);
    $failing->shouldReceive('overview', 'trend', 'breakdown')->andThrow($error);
    app()->instance(SiteAnalyticsServiceInterface::class, $failing);

    $admin = analyticsAdmin();
    $message = 'Google Analytics terlalu lama merespons';

    Livewire::actingAs($admin)->test(AnalyticsOverview::class, ['pageFilters' => ['period' => 'today']])->assertSee($message);
    Livewire::actingAs($admin)->test(AnalyticsTrendChart::class, ['pageFilters' => ['period' => 'today']])->assertSee($message);
    Livewire::actingAs($admin)->test(AnalyticsBreakdownList::class, ['breakdown' => 'pages', 'pageFilters' => ['period' => 'today']])->assertSee($message);
});

test('widgets render GA4 numbers for the period chosen on the page', function () {
    $service = Mockery::mock(SiteAnalyticsServiceInterface::class);
    $service->shouldReceive('overview')->with(AnalyticsPeriod::Last28Days)->andReturn([
        'current' => ['users' => 1250, 'sessions' => 1600, 'page_views' => 4200, 'average_session_seconds' => 83.0],
        'previous' => ['users' => 1000, 'sessions' => 1600, 'page_views' => 5000, 'average_session_seconds' => 80.0],
    ]);
    $service->shouldReceive('breakdown')->with(AnalyticsPeriod::Last28Days, AnalyticsBreakdown::Devices)->andReturn([
        ['label' => 'Ponsel', 'value' => 1200],
        ['label' => 'Desktop', 'value' => 400],
    ]);
    app()->instance(SiteAnalyticsServiceInterface::class, $service);

    $admin = analyticsAdmin();
    $filters = ['pageFilters' => ['period' => '28d']];

    Livewire::actingAs($admin)->test(AnalyticsOverview::class, $filters)
        ->assertSee('1.250')
        ->assertSee('Naik 25% dibanding 28 hari sebelumnya')
        ->assertSee('Turun 16% dibanding 28 hari sebelumnya')
        ->assertSee('1 mnt 23 dtk');

    Livewire::actingAs($admin)->test(AnalyticsBreakdownList::class, ['breakdown' => 'devices', ...$filters])
        ->assertSee('Perangkat')
        ->assertSee('Ponsel')
        ->assertSee('1.200 sesi');
});
