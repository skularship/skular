<?php

use App\Models\User;
use Filament\Facades\Filament;

test('the public pages declare light mode and never apply the dark class', function () {
    $response = $this->get(route('home'));

    $response->assertOk()
        ->assertSee('<meta name="color-scheme" content="light" />', false)
        ->assertDontSee("classList.toggle('dark'", false)
        ->assertDontSee("localStorage.getItem('appearance')", false);
});

test('the settings screens no longer offer an appearance preference', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $response = $this->actingAs($user)->get(route('profile.edit'));

    $response->assertOk()->assertDontSee('Appearance');
});

test('the appearance settings route has been removed', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)->get('/settings/appearance')->assertNotFound();
});

test('the admin panel runs without dark mode', function () {
    expect(Filament::getPanel('admin')->hasDarkMode())->toBeFalse();
});
