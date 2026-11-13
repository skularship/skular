<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Support\Seo;
use Carbon\CarbonImmutable;
use Illuminate\Http\Response;

/**
 * The public XML sitemap.
 *
 * Only pages a crawler can load without authenticating are listed; the
 * scholarship catalogue, learning modules, practice forms, dashboard, profile
 * and Filament admin all require a session. `lastmod` is taken from a real
 * content change (a date kept by hand in config/seo.php, or the newest
 * published FAQ) instead of the deploy or the request time.
 */
class SitemapController
{
    /**
     * Render the public sitemap.xml.
     *
     * @return Response XML response listing public pages only.
     */
    public function __invoke(): Response
    {
        return response()
            ->view('sitemap', ['urls' => $this->urls()])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    /**
     * @return array<int, array{loc: string, lastmod: string}>
     */
    private function urls(): array
    {
        return [
            [
                'loc' => Seo::url('/'),
                'lastmod' => $this->configuredLastModified('home'),
            ],
            [
                'loc' => Seo::url('/faq'),
                'lastmod' => $this->faqLastModified(),
            ],
        ];
    }

    /**
     * The hand-maintained date from config/seo.php `pages.{page}.lastmod`.
     *
     * @param  string  $page  Key under `seo.pages`, e.g. "home".
     */
    private function configuredLastModified(string $page): string
    {
        return CarbonImmutable::parse((string) config("seo.pages.{$page}.lastmod"))->format('Y-m-d');
    }

    /**
     * The FAQ page changes when its published questions change.
     */
    private function faqLastModified(): string
    {
        $latest = Faq::query()->published()->max('updated_at');

        return $latest === null
            ? $this->configuredLastModified('faq')
            : CarbonImmutable::parse($latest)->setTimezone(config('app.timezone'))->format('Y-m-d');
    }
}
