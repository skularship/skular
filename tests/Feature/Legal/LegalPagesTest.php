<?php

use App\Enums\LegalDocument;
use App\Models\User;
use App\Support\Seo;
use Illuminate\Support\Facades\Artisan;

/**
 * The four legal pages are public, carry their version and effective date,
 * are linked from every layout, avoid the forbidden wording, and the
 * legal:check guard blocks unfilled [ISI] markers in production.
 */
dataset('legal documents', fn (): array => array_map(
    fn (LegalDocument $document): array => [$document],
    LegalDocument::cases(),
));

/**
 * Visible text of a page: scripts, styles and tags removed, whitespace collapsed.
 */
function visibleText(string $html): string
{
    $html = (string) preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', ' ', $html);

    return trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($html))));
}

test('each legal page is public and shows its version and effective date', function (LegalDocument $document) {
    config([
        "legal.documents.{$document->value}.version" => '3.2',
        "legal.documents.{$document->value}.effective_at" => '2026-12-05',
    ]);

    $this->get(route($document->routeName()))
        ->assertOk()
        ->assertSee($document->label())
        ->assertSee('Versi 3.2, berlaku sejak 5 Desember 2026')
        ->assertSee('Intinya')
        ->assertSee('<link rel="canonical" href="'.Seo::url('/'.$document->path()).'">', false)
        ->assertSee('<title>'.e($document->label()).' — Skular</title>', false);
})->with('legal documents');

test('legal pages stay public while premium is switched off', function (LegalDocument $document) {
    config(['pricing.enabled' => false]);

    $this->get('/'.$document->path())->assertOk();
})->with('legal documents');

test('legal pages avoid forbidden wording', function (LegalDocument $document) {
    $text = mb_strtolower(visibleText($this->get(route($document->routeName()))->content()));

    expect($text)
        ->not->toContain('jaminan lolos')
        ->not->toContain('tidak dapat dikembalikan')
        ->not->toContain('partner')
        ->not->toContain('mitra')
        ->not->toContain('acceptance rate')
        ->not->toContain('90%')
        ->and(str_replace('situs resmi', '', $text))->not->toContain('resmi');
})->with('legal documents');

test('the appreciation programme section stays hidden while the feature is off', function () {
    config(['legal.features.success_appreciation' => false]);

    $this->get(route('legal.terms'))
        ->assertOk()
        ->assertDontSee('id="apresiasi"', false)
        ->assertDontSee('[KEPUTUSAN', false);
});

test('the landing, faq and auth footers link every legal page and the cookie settings', function (string $url) {
    $response = $this->get($url)->assertOk();

    foreach (LegalDocument::cases() as $document) {
        $response->assertSee('href="'.route($document->routeName()).'"', false);
    }

    $response->assertSee('data-cookie-settings', false)
        ->assertSee('data-test="cookie-consent"', false);
})->with([
    'landing' => fn () => route('home'),
    'faq' => fn () => route('faq.index'),
    'login' => fn () => route('login'),
    'register' => fn () => route('register'),
]);

test('the signed-in app layout links every legal page and carries the cookie banner', function () {
    $response = $this->actingAs(User::factory()->profiled()->create())
        ->get(route('dashboard'))
        ->assertOk();

    foreach (LegalDocument::cases() as $document) {
        $response->assertSee('href="'.route($document->routeName()).'"', false);
    }

    $response->assertSee('data-test="cookie-consent"', false);
});

test('the cookie banner sits in the page flow so it never covers forms or the pay button', function () {
    $html = $this->get(route('login'))->assertOk()->content();

    preg_match('/<section[^>]*data-test="cookie-consent"[^>]*>/', $html, $match);

    expect($match)->not->toBeEmpty()
        ->and($match[0])->not->toContain('fixed')
        ->not->toContain('absolute')
        ->not->toContain('sticky')
        ->and($html)->toContain('Terima semua')
        ->toContain('Tolak semua')
        ->toContain('Atur pilihan');
});

test('the public layout only marks faq as the current page on the faq page', function () {
    $this->get(route('legal.privacy'))
        ->assertOk()
        ->assertDontSee('aria-current="page"', false)
        ->assertSee('href="#konten-utama"', false);

    $this->get(route('faq.index'))
        ->assertOk()
        ->assertSee('aria-current="page"', false);
});

test('legal:check fails in production while a marker is left', function () {
    app()->detectEnvironment(fn (): string => 'production');
    config(['legal.contact.privacy_email' => '[ISI: email privasi]']);

    expect(Artisan::call('legal:check'))->toBe(1)
        ->and(Artisan::output())->toContain('[ISI: email privasi]');
});

test('legal:check only warns outside production unless strict', function () {
    config(['legal.contact.privacy_email' => '[ISI: email privasi]']);

    expect(Artisan::call('legal:check'))->toBe(0)
        ->and(Artisan::call('legal:check', ['--strict' => true]))->toBe(1);
});

test('legal:check passes once every value is filled', function () {
    app()->detectEnvironment(fn (): string => 'production');
    config([
        'legal.company.name' => 'PT Contoh Skular',
        'legal.company.address' => 'Jalan Contoh 1, Kota Contoh',
        'legal.company.registration' => '1234567890123',
        'legal.contact.email' => 'bantuan@example.com',
        'legal.contact.privacy_email' => 'privasi@example.com',
        'legal.jurisdiction_city' => 'Kota Contoh',
        'legal.hosting.server_location' => 'Indonesia',
        'legal.hosting.mail_provider' => 'Penyedia Email Contoh',
    ]);

    expect(Artisan::call('legal:check'))->toBe(0)
        ->and(Artisan::output())->toContain('bersih');
});
