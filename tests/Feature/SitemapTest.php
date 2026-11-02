<?php

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

test('the sitemap lastmod comes from the content, not from the request time', function () {
    $content = $this->get('/sitemap.xml')->assertOk()->content();

    $expected = CarbonImmutable::createFromTimestamp(
        (int) filemtime(resource_path('views/welcome.blade.php')),
        config('app.timezone'),
    )->format('Y-m-d');

    expect($content)->toContain('<lastmod>'.$expected.'</lastmod>');
});
