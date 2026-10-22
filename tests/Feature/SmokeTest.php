<?php

use App\Models\Module;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

test('the public landing page renders', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Skular');
});

test('every core mentee page renders with seeded demo data', function () {
    $this->seed(DatabaseSeeder::class);

    $mentee = User::query()->where('email', 'mentee.progress@skular.test')->firstOrFail();

    $this->actingAs($mentee)->get(route('profiling'))->assertOk()->assertSee('Profiling');
    $this->actingAs($mentee)->get(route('profiling.recommendation'))->assertOk();
    $this->actingAs($mentee)->get(route('dashboard'))->assertOk()->assertSee('Roadmap belajar');

    $module = Module::query()->ordered()->firstOrFail();

    $this->actingAs($mentee)->get(route('modules.show', $module))
        ->assertOk()
        ->assertSee($module->title);
});
