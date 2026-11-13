<?php

use App\Models\Faq;
use App\Support\Seo;
use Carbon\CarbonImmutable;

test('the sitemap returns valid xml', function () {
    $response = $this->get('/sitemap.xml')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

    $xml = simplexml_load_string($response->content());

    expect($xml)->not->toBeFalse()
        ->and($xml->getName())->toBe('urlset')
        ->and($xml->count())->toBe(2);
});

test('the sitemap only lists public pages on the canonical host', function () {
    $xml = simplexml_load_string($this->get('/sitemap.xml')->assertOk()->content());

    $locs = [];

    foreach ($xml->url as $url) {
        $locs[] = (string) $url->loc;
    }

    expect($locs)->toBe([Seo::url('/'), Seo::url('/faq')])
        ->and($locs)->toBe(array_values(array_unique($locs)));

    foreach ($locs as $loc) {
        expect($loc)
            ->toStartWith('https://')
            ->not->toContain('localhost')
            ->not->toContain('127.0.0.1')
            ->not->toContain('?');
    }
});

test('the sitemap never lists authenticated or internal pages', function () {
    $content = $this->get('/sitemap.xml')->assertOk()->content();

    foreach (['/dashboard', '/profiling', '/library', '/cv', '/scholarships', '/modules', '/settings', '/admin', '/login', '/register', '/health'] as $path) {
        expect($content)->not->toContain($path);
    }
});

test('the home lastmod is the configured date, not the view file time', function () {
    config(['seo.pages.home.lastmod' => '2026-01-15']);

    $xml = simplexml_load_string($this->get('/sitemap.xml')->assertOk()->content());

    expect((string) $xml->url[0]->loc)->toBe(Seo::url('/'))
        ->and((string) $xml->url[0]->lastmod)->toBe('2026-01-15');
});

test('the faq lastmod follows the newest published faq only', function () {
    config(['seo.pages.faq.lastmod' => '2026-01-15']);

    Faq::factory()->published()->create([
        'updated_at' => CarbonImmutable::parse('2026-03-10 09:00:00', config('app.timezone')),
    ]);

    Faq::factory()->draft()->create([
        'updated_at' => CarbonImmutable::parse('2026-05-20 09:00:00', config('app.timezone')),
    ]);

    $xml = simplexml_load_string($this->get('/sitemap.xml')->assertOk()->content());

    expect((string) $xml->url[1]->lastmod)->toBe('2026-03-10');
});

test('the faq lastmod falls back to the configured date without published faqs', function () {
    config(['seo.pages.faq.lastmod' => '2026-02-01']);

    $xml = simplexml_load_string($this->get('/sitemap.xml')->assertOk()->content());

    expect((string) $xml->url[1]->lastmod)->toBe('2026-02-01');
});
