<?php

test('readiness health check reports database and cache without internals', function () {
    $this->get(route('health'))
        ->assertOk()
        ->assertJson([
            'status' => 'ok',
            'checks' => [
                'database' => 'ok',
                'cache' => 'ok',
            ],
        ])
        ->assertJsonMissingPath('exception')
        ->assertJsonMissingPath('trace');
});

test('every response carries baseline security headers', function () {
    $this->get(route('health'))
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
});

test('hsts is only sent for https requests', function () {
    $this->get(route('health'))->assertHeaderMissing('Strict-Transport-Security');

    $this->get('https://localhost/health')->assertHeader('Strict-Transport-Security');
});
