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
 * content change (the view file, or the newest published FAQ) instead of the
 * time the sitemap happened to be requested.
 */
class SitemapController
{
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
                'lastmod' => $this->lastModified(resource_path('views/welcome.blade.php')),
            ],
            [
                'loc' => Seo::url('/faq'),
                'lastmod' => $this->faqLastModified(),
            ],
        ];
    }

    /**
     * The file's own modification time: the last time the copy changed.
     */
    private function lastModified(string $path): string
    {
        return CarbonImmutable::createFromTimestamp((int) filemtime($path), config('app.timezone'))
            ->format('Y-m-d');
    }

    /**
     * The FAQ page changes when its published questions change.
     */
    private function faqLastModified(): string
    {
        $latest = Faq::query()->published()->max('updated_at');

        return $latest === null
            ? $this->lastModified(resource_path('views/livewire/faq/index.blade.php'))
            : CarbonImmutable::parse($latest)->setTimezone(config('app.timezone'))->format('Y-m-d');
    }
}
