<?php

use App\Enums\AdminRole;
use App\Filament\Resources\Modules\ModuleResource;
use App\Filament\Resources\UserPracticeResponses\UserPracticeResponseResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\Module;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Create an admin user with the given role and the listed Shield permissions.
 *
 * @param  array<int, string>  $permissions
 */
function adminUser(string $role, array $permissions = []): User
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

test('the admin login page is reachable by guests', function () {
    $this->get('/admin/login')->assertOk();
});

test('a regular mentee without an admin role cannot access the admin panel', function () {
    $mentee = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($mentee)
        ->get('/admin')
        ->assertForbidden();
});

test('a staff divisi user sees content but cannot delete it', function () {
    $staff = adminUser(AdminRole::StaffDivisi->value, [
        'ViewAny:Module', 'View:Module', 'Create:Module', 'Update:Module',
    ]);

    $module = Module::factory()->create();

    $this->actingAs($staff)->get('/admin')->assertOk();
    $this->actingAs($staff)->get(ModuleResource::getUrl('index'))->assertOk();

    expect(Gate::forUser($staff)->allows('delete', $module))->toBeFalse();
    expect(Gate::forUser($staff)->allows('update', $module))->toBeTrue();

    // Staff do not manage master data such as clusters.
    $this->actingAs($staff)->get('/admin/clusters')->assertForbidden();
});

test('a co-admin can manage content but not users or roles', function () {
    $co_admin = adminUser(AdminRole::CoAdmin->value, [
        'ViewAny:Module', 'View:Module', 'Create:Module', 'Update:Module', 'Delete:Module', 'DeleteAny:Module',
        'ViewAny:User', 'View:User',
        'ViewAny:UserPracticeResponse', 'View:UserPracticeResponse',
    ]);

    $module = Module::factory()->create();
    $other = User::factory()->create();

    expect(Gate::forUser($co_admin)->allows('delete', $module))->toBeTrue();

    // Co-admin may list users but cannot edit them.
    $this->actingAs($co_admin)->get(UserResource::getUrl('index'))->assertOk();
    $this->actingAs($co_admin)->get(UserResource::getUrl('edit', ['record' => $other]))->assertForbidden();

    // Co-admin cannot manage roles and permissions.
    $this->actingAs($co_admin)->get('/admin/shield/roles')->assertForbidden();

    // Co-admin can review the read-only practice answers queue.
    $this->actingAs($co_admin)->get(UserPracticeResponseResource::getUrl('index'))->assertOk();
});

test('a super admin bypasses permission checks through the shield gate', function () {
    $super_admin = adminUser(AdminRole::SuperAdmin->value);

    $module = Module::factory()->create();
    $other = User::factory()->create();

    expect(Gate::forUser($super_admin)->allows('delete', $module))->toBeTrue();

    $this->actingAs($super_admin)->get('/admin')->assertOk();
    $this->actingAs($super_admin)->get('/admin/shield/roles')->assertOk();
    $this->actingAs($super_admin)->get(UserResource::getUrl('edit', ['record' => $other]))->assertOk();
    $this->actingAs($super_admin)->get(UserPracticeResponseResource::getUrl('index'))->assertOk();
});

test('authorization is denied on the backend even without navigating the ui', function () {
    $staff = adminUser(AdminRole::StaffDivisi->value, ['ViewAny:Module', 'View:Module']);

    $module = Module::factory()->create();

    // Creating is not granted to this limited staff role, so the backend rejects it.
    $this->actingAs($staff)->get(ModuleResource::getUrl('create'))->assertForbidden();
});
