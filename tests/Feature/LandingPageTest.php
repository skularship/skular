<?php

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

test('the hero rotates through every headline phrase', function () {
    $response = $this->get(route('home'))->assertOk();

    $response->assertSee('Siapkan Dirimu.')
        ->assertSee('Kejar Beasiswamu.')
        ->assertSee('sk-word', false);

    foreach (['Lebih Siap.', 'Lebih Terarah.', 'Lebih Percaya Diri.'] as $phrase) {
        $response->assertSee($phrase);
    }
});

test('both marquees repeat their content so the loop is seamless', function () {
    $content = $this->get(route('home'))->content();

    // Two identical tracks per marquee: one readable, one hidden from the
    // accessibility tree, which is what lets the -50% translation loop.
    expect(substr_count($content, 'Temukan Beasiswamu'))->toBe(2)
        ->and(substr_count($content, 'Masa Depan Dimulai Sekarang'))->toBe(2)
        ->and(substr_count($content, 'Berprestasi'))->toBe(2)
        ->and(substr_count($content, 'sk-marquee--reverse'))->toBe(1);
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
