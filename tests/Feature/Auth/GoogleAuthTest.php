<?php

use App\Exceptions\GoogleAuthException;
use App\Models\User;
use App\Services\GoogleAuthService;

test('an unverified google email cannot be linked to an existing account', function () {
    $user = User::factory()->create([
        'email' => 'mentee@example.com',
        'google_id' => null,
    ]);

    $service = app(GoogleAuthService::class);

    expect(fn () => $service->resolveUser([
        'id' => 'google-123',
        'name' => 'Mentee',
        'email' => 'mentee@example.com',
        'email_verified' => false,
    ]))->toThrow(GoogleAuthException::class);

    expect($user->refresh()->google_id)->toBeNull();
});

test('a verified google email links to the matching local account', function () {
    $user = User::factory()->create([
        'email' => 'mentee@example.com',
        'google_id' => null,
        'email_verified_at' => null,
    ]);

    $service = app(GoogleAuthService::class);

    $resolved = $service->resolveUser([
        'id' => 'google-123',
        'name' => 'Mentee',
        'email' => 'mentee@example.com',
        'email_verified' => true,
    ]);

    expect($resolved->is($user))->toBeTrue()
        ->and($resolved->google_id)->toBe('google-123')
        ->and($resolved->email_verified_at)->not->toBeNull();
});

test('a new verified google account is created without a usable password', function () {
    $service = app(GoogleAuthService::class);

    $user = $service->resolveUser([
        'id' => 'google-456',
        'name' => 'New Mentee',
        'email' => 'new@example.com',
        'email_verified' => true,
    ]);

    expect($user->exists)->toBeTrue()
        ->and($user->google_id)->toBe('google-456')
        ->and($user->email_verified_at)->not->toBeNull();
});
