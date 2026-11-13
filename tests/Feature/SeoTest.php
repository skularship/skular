<?php

use App\Models\User;
use App\Support\Seo;

test('the landing page exposes one set of indexable metadata', function () {
    $html = $this->get(route('home'))->assertOk()->content();

    expect(substr_count($html, '<title>'))->toBe(1)
        ->and(substr_count($html, 'name="description"'))->toBe(1)
        ->and(substr_count($html, 'name="robots"'))->toBe(1)
        ->and(substr_count($html, 'rel="canonical"'))->toBe(1)
        ->and(substr_count($html, 'property="og:title"'))->toBe(1)
        ->and(substr_count($html, 'property="og:url"'))->toBe(1)
        ->and(substr_count($html, 'name="twitter:card"'))->toBe(1);

    $title = config('seo.defaults.title');
    $description = config('seo.defaults.description');
    $canonical = Seo::url('/');
    $image = Seo::imageUrl();

    expect($html)
        ->toContain('<title>'.$title.'</title>')
        ->toContain('<meta name="description" content="'.$description.'">')
        ->toContain('<meta name="robots" content="index, follow">')
        ->toContain('<link rel="canonical" href="'.$canonical.'">')
        ->toContain('<meta property="og:title" content="'.$title.'">')
        ->toContain('<meta property="og:description" content="'.$description.'">')
        ->toContain('<meta property="og:url" content="'.$canonical.'">')
        ->toContain('<meta property="og:image" content="'.$image.'">')
        ->toContain('<meta property="og:image:alt" content="'.config('seo.image.alt').'">')
        ->toContain('<meta name="twitter:image" content="'.$image.'">')
        ->toContain('<meta property="og:locale" content="id_ID">')
        ->toContain('application/ld+json');

    // Only the canonical/social URLs are production addresses; ordinary page
    // links and asset preloads follow APP_URL, which is the production host
    // once deployed.
    $metadata = collect(explode("\n", $html))
        ->filter(fn (string $line): bool => str_contains($line, 'rel="canonical"')
            || str_contains($line, 'property="og:')
            || str_contains($line, 'name="twitter:'))
        ->implode("\n");

    expect($metadata)
        ->not->toContain('localhost')
        ->not->toContain('http://');
});

test('the canonical url drops query strings and preview hosts', function () {
    $html = $this->get(route('faq.index', ['q' => 'beasiswa', 'utm_source' => 'newsletter']))
        ->assertOk()
        ->content();

    expect($html)
        ->toContain('<link rel="canonical" href="'.Seo::url('/faq').'">')
        ->toContain('<meta property="og:url" content="'.Seo::url('/faq').'">')
        ->not->toContain('utm_source');
});

test('authenticated pages are noindex', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
});

test('authentication screens are noindex but followable', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('<meta name="robots" content="noindex, follow">', false);
});

test('robots.txt allows public crawling and points at the canonical sitemap', function () {
    $robots = file_get_contents(public_path('robots.txt'));

    expect($robots)
        ->toContain('User-agent: *')
        ->toContain('Allow: /')
        ->toContain('Sitemap: '.config('seo.url').'/sitemap.xml')
        ->not->toContain('localhost')
        ->not->toContain('127.0.0.1')
        ->not->toContain('Disallow: /css')
        ->not->toContain('Disallow: /build')
        ->not->toContain('Disallow: /js')
        ->not->toContain('Disallow: /fonts');
});

test('the shared social preview is a real 1200x630 asset', function () {
    $path = public_path(ltrim(config('seo.image.path'), '/'));

    expect(file_exists($path))->toBeTrue();

    [$width, $height] = getimagesize($path);

    expect($width)->toBe(1200)
        ->and($height)->toBe(630)
        ->and(filesize($path))->toBeLessThan(1_000_000);
});

test('an unknown url renders the branded 404 page with a real 404 status', function () {
    $this->get('/halaman-yang-tidak-ada')
        ->assertNotFound()
        ->assertSee('Halaman Tidak Ditemukan', false)
        ->assertSee('<meta name="robots" content="noindex, follow" />', false)
        ->assertSee('/skular-logo.png', false);
});

test('the admin panel favicon is root relative so nested admin urls resolve it', function () {
    $this->get('/admin/login')
        ->assertOk()
        ->assertSee('<link rel="icon" href="/favicon.png" />', false);
});

test('public pages link the square favicon, touch icon, manifest and theme colour', function () {
    $html = $this->get(route('home'))->assertOk()->content();

    expect($html)
        ->toContain('<link rel="icon" href="/favicon.ico" sizes="any">')
        ->toContain('<link rel="icon" href="/favicon-48x48.png" type="image/png" sizes="48x48">')
        ->toContain('<link rel="apple-touch-icon" href="/apple-touch-icon.png">')
        ->toContain('<link rel="manifest" href="/site.webmanifest">')
        ->toContain('<meta name="theme-color" content="'.config('seo.theme_color').'">')
        ->and(substr_count($html, 'rel="manifest"'))->toBe(1);
});

test('every icon is a real square asset of the advertised size', function (string $file, int $size) {
    $path = public_path($file);

    expect(file_exists($path))->toBeTrue();

    [$width, $height] = getimagesize($path);

    expect($width)->toBe($size)->and($height)->toBe($size);
})->with([
    'png favicon' => ['favicon-48x48.png', 48],
    'apple touch icon' => ['apple-touch-icon.png', 180],
    'manifest 192' => ['icon-192.png', 192],
    'manifest 512' => ['icon-512.png', 512],
]);

test('favicon.ico holds 16, 32 and 48 pixel frames', function () {
    $ico = file_get_contents(public_path('favicon.ico'));

    $header = unpack('vreserved/vtype/vcount', substr($ico, 0, 6));

    expect($header)->toBe(['reserved' => 0, 'type' => 1, 'count' => 3]);

    $sizes = [];

    for ($i = 0; $i < 3; $i++) {
        $sizes[] = unpack('Cwidth', substr($ico, 6 + 16 * $i, 1))['width'];
    }

    expect($sizes)->toBe([16, 32, 48]);
});

test('the web manifest is valid and points at existing brand icons', function () {
    $manifest = json_decode(file_get_contents(public_path('site.webmanifest')), true, flags: JSON_THROW_ON_ERROR);

    expect($manifest)
        ->name->toBe('Skular')
        ->short_name->toBe('Skular')
        ->start_url->toBe('/')
        ->display->toBe('browser')
        ->theme_color->toBe(config('seo.theme_color'))
        ->background_color->toBe('#fdfaef');

    expect(array_column($manifest['icons'], 'sizes'))->toBe(['192x192', '512x512']);

    foreach ($manifest['icons'] as $icon) {
        expect(file_exists(public_path(ltrim($icon['src'], '/'))))->toBeTrue();
    }
});
