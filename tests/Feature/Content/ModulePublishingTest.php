<?php

use App\Enums\AdminRole;
use App\Enums\ContentStatus;
use App\Filament\Resources\Modules\Pages\EditModule;
use App\Filament\Resources\Modules\Pages\ListModules;
use App\Models\Module;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Create a panel user with the given role and Shield permissions.
 *
 * @param  array<int, string>  $permissions
 */
function publishingUser(string $role, array $permissions = []): User
{
    $role_model = Role::findOrCreate($role, 'web');

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $role_model->syncPermissions($permissions);

    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole($role_model);

    return $user;
}

test('an incomplete module cannot be published from the editor', function () {
    $admin = publishingUser(AdminRole::SuperAdmin->value, ['ViewAny:Module', 'View:Module', 'Update:Module']);

    $module = Module::factory()->draft()->create([
        'summary_material' => null,
        'inti_material' => null,
        'insights' => null,
    ]);

    Livewire::actingAs($admin)
        ->test(EditModule::class, ['record' => $module->getRouteKey()])
        ->callAction('publish')
        ->assertNotified();

    expect($module->refresh()->content_status)->toBe(ContentStatus::Draft);
});

test('a complete module is published from the editor', function () {
    $admin = publishingUser(AdminRole::SuperAdmin->value, ['ViewAny:Module', 'View:Module', 'Update:Module']);

    $module = Module::factory()->draft()->create();

    Livewire::actingAs($admin)
        ->test(EditModule::class, ['record' => $module->getRouteKey()])
        ->callAction('publish')
        ->assertNotified();

    expect($module->refresh()->content_status)->toBe(ContentStatus::Published);
});

test('a published module can be returned to draft', function () {
    $admin = publishingUser(AdminRole::SuperAdmin->value, ['ViewAny:Module', 'View:Module', 'Update:Module']);

    $module = Module::factory()->create(['content_status' => ContentStatus::Published]);

    Livewire::actingAs($admin)
        ->test(EditModule::class, ['record' => $module->getRouteKey()])
        ->callAction('unpublish')
        ->assertNotified();

    expect($module->refresh()->content_status)->toBe(ContentStatus::Draft);
});

test('the editor hides the publish action once the module is published', function () {
    $admin = publishingUser(AdminRole::SuperAdmin->value, ['ViewAny:Module', 'View:Module', 'Update:Module']);

    $module = Module::factory()->create(['content_status' => ContentStatus::Published]);

    Livewire::actingAs($admin)
        ->test(EditModule::class, ['record' => $module->getRouteKey()])
        ->assertActionHidden('publish')
        ->assertActionVisible('unpublish');
});

test('a user without update permission cannot publish a module', function () {
    $staff = publishingUser(AdminRole::StaffDivisi->value, [
        'ViewAny:Module', 'View:Module', 'Create:Module',
    ]);

    $module = Module::factory()->draft()->create();

    // The draft module makes the action relevant, so hiding it here can only
    // come from the update ability being denied.
    Livewire::actingAs($staff)
        ->test(ListModules::class)
        ->assertActionHidden(TestAction::make('publish')->table($module));

    // The backend refuses the same operation even without the button.
    expect(Gate::forUser($staff)->allows('update', $module))->toBeFalse();

    $this->actingAs($staff)
        ->get(EditModule::getUrl(['record' => $module]))
        ->assertForbidden();

    expect($module->refresh()->content_status)->toBe(ContentStatus::Draft);
});

test('bulk publishing only publishes the modules that are ready', function () {
    $admin = publishingUser(AdminRole::SuperAdmin->value, ['ViewAny:Module', 'View:Module', 'Update:Module']);

    $ready = Module::factory()->draft()->create(['title' => 'Modul Siap Terbit']);
    $incomplete = Module::factory()->draft()->create([
        'title' => 'Modul Belum Lengkap',
        'summary_material' => null,
        'inti_material' => null,
        'insights' => null,
    ]);

    Livewire::actingAs($admin)
        ->test(ListModules::class)
        ->selectTableRecords([$ready, $incomplete])
        ->mountTableBulkAction('publishSelected', [$ready, $incomplete])
        ->callMountedAction();

    expect($ready->refresh()->content_status)->toBe(ContentStatus::Published)
        ->and($incomplete->refresh()->content_status)->toBe(ContentStatus::Draft);
});
