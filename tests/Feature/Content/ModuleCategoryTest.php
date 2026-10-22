<?php

use App\Enums\AdminRole;
use App\Enums\ModuleCategory;
use App\Filament\Resources\Modules\Pages\CreateModule;
use App\Models\Fase;
use App\Models\Module;
use App\Models\Scholarship;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Create an authenticated super admin able to use the Filament panel.
 */
function categoryAdmin(): User
{
    $role = Role::findOrCreate(AdminRole::SuperAdmin->value, 'web');
    Permission::findOrCreate('ViewAny:Module', 'web');
    $role->syncPermissions(['ViewAny:Module']);

    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole($role);

    return $user;
}

test('an inti module cannot keep a scholarship relation', function () {
    $scholarship = Scholarship::factory()->create();

    $module = Module::factory()->create([
        'category' => ModuleCategory::Inti,
        'scholarship_id' => $scholarship->id,
    ]);

    expect($module->refresh()->scholarship_id)->toBeNull();
});

test('a perpustakaan module cannot keep a scholarship relation', function () {
    $scholarship = Scholarship::factory()->create();

    $module = Module::factory()->library()->create([
        'scholarship_id' => $scholarship->id,
    ]);

    expect($module->refresh()->scholarship_id)->toBeNull();
});

test('a template module keeps its scholarship relation', function () {
    $scholarship = Scholarship::factory()->create();

    $module = Module::factory()->template($scholarship)->create();

    expect($module->refresh()->scholarship_id)->toBe($scholarship->id)
        ->and($module->category)->toBe(ModuleCategory::Template)
        ->and($module->isTemplate())->toBeTrue();
});

test('switching a template module to inti clears the stale scholarship', function () {
    $scholarship = Scholarship::factory()->create();
    $module = Module::factory()->template($scholarship)->create();

    $module->update(['category' => ModuleCategory::Inti]);

    expect($module->refresh()->scholarship_id)->toBeNull();
});

test('a scholarship exposes its template modules', function () {
    $scholarship = Scholarship::factory()->create();
    Module::factory()->template($scholarship)->create(['modul_order' => 2]);
    Module::factory()->create();

    expect($scholarship->templateModules()->count())->toBe(1);
});

test('the module form requires a scholarship when template is selected', function () {
    $admin = categoryAdmin();
    $fase = Fase::factory()->create();

    Livewire::actingAs($admin)
        ->test(CreateModule::class)
        ->fillForm([
            'fase_id' => $fase->id,
            'title' => 'Modul Template Uji',
            'modul_order' => 1,
            'category' => ModuleCategory::Template->value,
            'type' => 'reading',
        ])
        ->call('create')
        ->assertHasFormErrors(['scholarship_id']);
});

test('the module form stores a template module with its scholarship', function () {
    $admin = categoryAdmin();
    $fase = Fase::factory()->create();
    $scholarship = Scholarship::factory()->create();

    Livewire::actingAs($admin)
        ->test(CreateModule::class)
        ->fillForm([
            'fase_id' => $fase->id,
            'title' => 'Modul Template Beasiswa A',
            'modul_order' => 1,
            'category' => ModuleCategory::Template->value,
            'type' => 'reading',
            'scholarship_id' => $scholarship->id,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $module = Module::query()->where('title', 'Modul Template Beasiswa A')->first();

    expect($module)->not->toBeNull()
        ->and($module->category)->toBe(ModuleCategory::Template)
        ->and($module->scholarship_id)->toBe($scholarship->id);
});
