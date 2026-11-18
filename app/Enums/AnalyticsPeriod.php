<?php

namespace App\Enums;

/**
 * Reporting periods offered on the admin Google Analytics page.
 *
 * Dates are GA4 relative strings ("today", "7daysAgo") so Google resolves
 * them in the property's own time zone, and every period has a previous
 * period of the same length to compare against.
 */
enum AnalyticsPeriod: string
{
    case Today = 'today';
    case Yesterday = 'yesterday';
    case Last7Days = '7d';
    case Last28Days = '28d';
    case Last90Days = '90d';

    /**
     * Label shown in the period filter.
     *
     * @return string Indonesian label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Today => 'Hari ini',
            self::Yesterday => 'Kemarin',
            self::Last7Days => '7 hari terakhir',
            self::Last28Days => '28 hari terakhir',
            self::Last90Days => '90 hari terakhir',
        };
    }

    /**
     * Phrase that describes what the trend percentage is compared with.
     *
     * @return string Indonesian phrase, e.g. "dibanding kemarin".
     */
    public function comparisonLabel(): string
    {
        return match ($this) {
            self::Today => 'dibanding kemarin',
            self::Yesterday => 'dibanding hari sebelumnya',
            self::Last7Days => 'dibanding 7 hari sebelumnya',
            self::Last28Days => 'dibanding 28 hari sebelumnya',
            self::Last90Days => 'dibanding 90 hari sebelumnya',
        };
    }

    /**
     * The reported date range. Multi-day periods end yesterday, like the GA4 UI, because today is still incomplete.
     *
     * @return array{start: string, end: string}
     */
    public function currentRange(): array
    {
        return match ($this) {
            self::Today => ['start' => 'today', 'end' => 'today'],
            self::Yesterday => ['start' => 'yesterday', 'end' => 'yesterday'],
            self::Last7Days => ['start' => '7daysAgo', 'end' => 'yesterday'],
            self::Last28Days => ['start' => '28daysAgo', 'end' => 'yesterday'],
            self::Last90Days => ['start' => '90daysAgo', 'end' => 'yesterday'],
        };
    }

    /**
     * The equally long range right before currentRange(), used for the trend percentage.
     *
     * @return array{start: string, end: string}
     */
    public function previousRange(): array
    {
        return match ($this) {
            self::Today => ['start' => 'yesterday', 'end' => 'yesterday'],
            self::Yesterday => ['start' => '2daysAgo', 'end' => '2daysAgo'],
            self::Last7Days => ['start' => '14daysAgo', 'end' => '8daysAgo'],
            self::Last28Days => ['start' => '56daysAgo', 'end' => '29daysAgo'],
            self::Last90Days => ['start' => '180daysAgo', 'end' => '91daysAgo'],
        };
    }

    /**
     * Whether the period covers a single day (the trend chart then groups by hour).
     */
    public function isSingleDay(): bool
    {
        return $this === self::Today || $this === self::Yesterday;
    }

    /**
     * How long a report for this period may be served from cache.
     *
     * Today's numbers keep changing, so they are refreshed every few minutes;
     * finished days change only while GA4 processes late hits.
     *
     * @return int Seconds.
     */
    public function cacheSeconds(): int
    {
        return $this === self::Today ? 300 : 3600;
    }

    /**
     * Options for a select field, keyed by value.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
