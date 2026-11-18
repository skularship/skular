<?php

use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register and land on the scholarship catalog', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'John Doe',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'terms' => '1',
        'age_confirmation' => '1',
    ]);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('scholarships.index', absolute: false));

    $this->assertAuthenticated();
});
