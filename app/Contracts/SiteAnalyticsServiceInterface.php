<?php

namespace App\Contracts;

use App\Enums\AnalyticsBreakdown;
use App\Enums\AnalyticsPeriod;
use App\Exceptions\AnalyticsUnavailableException;

/**
 * Reads website traffic from the Google Analytics 4 Data API for the admin
 * analytics page: one cached, time-limited report per widget and period.
 */
interface SiteAnalyticsServiceInterface
{
    /**
     * Whether a property id and service-account credentials are present.
     */
    public function isConfigured(): bool;

    /**
     * The service-account email that must be granted access to the property.
     *
     * @return string|null The client_email from the credentials, or null when unreadable.
     */
    public function serviceAccountEmail(): ?string;

    /**
     * Headline totals for the period and the previous period of the same length.
     *
     * @param  AnalyticsPeriod  $period  The reporting period.
     * @return array{current: array{users: int, sessions: int, page_views: int, average_session_seconds: float}, previous: array{users: int, sessions: int, page_views: int, average_session_seconds: float}}
     *
     * @throws AnalyticsUnavailableException When GA4 is not configured or the report fails.
     */
    public function overview(AnalyticsPeriod $period): array;

    /**
     * Visitors and page views per day, or per hour for a single-day period.
     *
     * @param  AnalyticsPeriod  $period  The reporting period.
     * @return list<array{label: string, users: int, page_views: int}> Points in chronological order.
     *
     * @throws AnalyticsUnavailableException When GA4 is not configured or the report fails.
     */
    public function trend(AnalyticsPeriod $period): array;

    /**
     * Top 10 rows of a breakdown (pages, sources, devices, countries).
     *
     * @param  AnalyticsPeriod  $period  The reporting period.
     * @param  AnalyticsBreakdown  $breakdown  Which list to load.
     * @return list<array{label: string, value: int}> Rows ranked by value, highest first.
     *
     * @throws AnalyticsUnavailableException When GA4 is not configured or the report fails.
     */
    public function breakdown(AnalyticsPeriod $period, AnalyticsBreakdown $breakdown): array;
}
