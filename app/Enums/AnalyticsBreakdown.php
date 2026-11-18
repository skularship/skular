<?php

namespace App\Enums;

/**
 * Top-10 lists on the admin Google Analytics page: which GA4 dimension is
 * grouped, which metric ranks it, and how the list is labelled.
 */
enum AnalyticsBreakdown: string
{
    case Pages = 'pages';
    case Sources = 'sources';
    case Devices = 'devices';
    case Countries = 'countries';

    /**
     * GA4 dimension the rows are grouped by.
     */
    public function dimension(): string
    {
        return match ($this) {
            self::Pages => 'pagePath',
            self::Sources => 'sessionSourceMedium',
            self::Devices => 'deviceCategory',
            self::Countries => 'country',
        };
    }

    /**
     * GA4 metric the rows are ranked by.
     */
    public function metric(): string
    {
        return $this === self::Pages ? 'screenPageViews' : 'sessions';
    }

    /**
     * Widget heading.
     */
    public function heading(): string
    {
        return match ($this) {
            self::Pages => 'Halaman paling dilihat',
            self::Sources => 'Sumber kunjungan',
            self::Devices => 'Perangkat',
            self::Countries => 'Negara',
        };
    }

    /**
     * Unit printed after each number, e.g. "120 sesi".
     */
    public function unit(): string
    {
        return $this === self::Pages ? 'tayangan' : 'sesi';
    }

    /**
     * Make a raw GA4 dimension value readable for the admin.
     *
     * @param  string  $value  Raw dimension value, e.g. "(direct) / (none)".
     * @return string Display label.
     */
    public function displayLabel(string $value): string
    {
        if ($value === '' || $value === '(not set)') {
            return 'Tidak diketahui';
        }

        return match ($this) {
            self::Sources => $value === '(direct) / (none)' ? 'Langsung (tanpa rujukan)' : $value,
            self::Devices => match ($value) {
                'desktop' => 'Desktop',
                'mobile' => 'Ponsel',
                'tablet' => 'Tablet',
                default => ucfirst($value),
            },
            default => $value,
        };
    }
}
