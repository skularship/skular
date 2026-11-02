<?php

namespace App\Support;

/**
 * Single source for the absolute URLs Skular publishes to crawlers.
 *
 * Every URL is built from the configured canonical host, never from the
 * incoming Host header, so a request through localhost or a preview domain
 * cannot leak into a canonical tag, a sitemap entry, or a social preview.
 */
class Seo
{
    /**
     * Canonical host without a trailing slash.
     */
    public static function baseUrl(): string
    {
        return rtrim((string) config('seo.url'), '/');
    }

    /**
     * Build an absolute canonical URL for a public path.
     */
    public static function url(string $path = '/'): string
    {
        $path = '/'.ltrim($path, '/');

        return $path === '/' ? self::baseUrl().'/' : self::baseUrl().rtrim($path, '/');
    }

    /**
     * Canonical URL for the current request: production host, no query string.
     */
    public static function canonical(): string
    {
        return self::url(request()->path());
    }

    /**
     * Absolute URL of the shared social preview image.
     */
    public static function imageUrl(): string
    {
        return self::url((string) config('seo.image.path'));
    }
}
