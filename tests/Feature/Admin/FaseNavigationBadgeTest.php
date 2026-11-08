<?php

use App\Enums\AdminRole;
use App\Filament\Resources\Fases\FaseResource;
use App\Models\Fase;
use App\Models\Module;
use App\Models\User;
use Spatie\Permission\Models\Role;

test('the fase badge is hidden when no module is a draft', function () {
    Module::factory()->count(2)->create();

    expect(FaseResource::getNavigationBadge())->toBeNull();
});

test('the fase badge counts draft modules only', function () {
    $fase = Fase::factory()->create();

    Module::factory()->count(3)->draft()->create(['fase_id' => $fase->id]);
    Module::factory()->count(2)->create(['fase_id' => $fase->id]);

    expect(FaseResource::getNavigationBadge())->toBe('3');
});

test('the admin panel renders with the draft badges in the navigation', function () {
    Module::factory()->draft()->create();

    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole(Role::findOrCreate(AdminRole::SuperAdmin->value, 'web'));

    $this->actingAs($admin)
        ->get(FaseResource::getUrl('index'))
        ->assertOk()
        ->assertSee('Modul berstatus draf di dalam fase ini', false);
});
