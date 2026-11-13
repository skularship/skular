<?php

use App\Enums\AiScoreLabel;

test('the landing page still answers on the home route', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Skular');
});

test('every landing section keeps an anchor for the navigation', function () {
    $response = $this->get(route('home'))->assertOk();

    foreach (['manfaat', 'cara-kerja', 'cek-kesiapan', 'persiapan', 'mulai'] as $anchor) {
        $response->assertSee('id="'.$anchor.'"', false);
    }

    $response->assertSee('href="#cek-kesiapan"', false)
        ->assertSee('id="landing-mobile-menu"', false);
});

test('the hero carries the positioning in the single h1', function () {
    $content = $this->get(route('home'))->assertOk()->content();

    // The heading words are wrapped one per span for the entrance animation,
    // so compare the readable text instead of the raw markup.
    $text = preg_replace('/\s+/', ' ', strip_tags($content));

    expect(substr_count($content, '<h1'))->toBe(1)
        ->and($text)->toContain('Dari bingung sampai submit, untuk beberapa beasiswa sekaligus.')
        ->and($content)->toContain('Satu kali persiapan untuk banyak beasiswa.')
        ->and($content)->toContain('Informasi Beasiswa')
        ->and($content)->toContain('Step-by-step Daftar');
});

test('the fact strip repeats its content so the loop is seamless', function () {
    $content = $this->get(route('home'))->content();

    // Two identical tracks: one readable, one hidden from the accessibility
    // tree, which is what lets the -50% translation loop.
    expect(substr_count($content, 'Tracker pendaftaran beasiswa'))->toBe(2)
        ->and(substr_count($content, 'sk-marquee__track'))->toBe(2);
});

test('the landing page shows no invented statistics or numeric scores', function () {
    $content = $this->get(route('home'))->content();

    foreach (['92%', '62%', '67%', 'Skor 88', 'Skor rubrik', '24/7'] as $invented) {
        expect($content)->not->toContain($invented);
    }
});

test('practice feedback is explained with the mentee labels', function () {
    $response = $this->get(route('home'))->assertOk();

    foreach (AiScoreLabel::cases() as $label) {
        $response->assertSee($label->label());
    }
});

test('the scholarship disclaimer is shown', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('tidak berafiliasi dengan penyelenggara beasiswa mana pun')
        ->assertSee('selalu gratis dan dilakukan di situs resmi penyelenggaranya')
        ->assertSee('data-test="landing-disclaimer"', false);
});

test('the calls to action point at the real authentication routes', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee(route('register'), false)
        ->assertSee(route('login'), false);
});

test('the preview panels are labelled as illustrations instead of real data', function () {
    $response = $this->get(route('home'))->assertOk();

    $response->assertSee('Ilustrasi tampilan')
        ->assertSee('Ilustrasi');
});

test('the landing page makes no guarantee about winning a scholarship', function () {
    $content = strtolower($this->get(route('home'))->content());

    foreach (['dijamin', 'pasti lolos', 'jaminan lolos', 'tingkat kelulusan', 'testimoni'] as $claim) {
        expect($content)->not->toContain($claim);
    }
});
