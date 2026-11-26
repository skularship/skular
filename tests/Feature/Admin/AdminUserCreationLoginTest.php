<?php

use App\Enums\AdminRole;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Models\User;
use Filament\Auth\Pages\Login;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/**
 * Sign in as a Super Admin, the only role allowed to assign admin roles.
 */
function actingAsSuperAdmin(): User
{
    $super_admin = User::factory()->create(['email_verified_at' => now()]);
    $super_admin->assignRole(Role::findOrCreate(AdminRole::SuperAdmin->value, 'web'));

    test()->actingAs($super_admin);

    return $super_admin;
}

test('an admin account created from the user resource can sign in to the admin panel', function () {
    $staff_role = Role::findOrCreate(AdminRole::StaffModul->value, 'web');
    actingAsSuperAdmin();

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Staf Baru',
            'email' => 'staf.baru@example.com',
            'password' => 'rahasia-panjang-123',
            'roles' => [$staff_role->id],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $created = User::query()->where('email', 'staf.baru@example.com')->firstOrFail();

    Auth::logout();

    Livewire::test(Login::class)
        ->fillForm([
            'email' => 'staf.baru@example.com',
            'password' => 'rahasia-panjang-123',
        ])
        ->call('authenticate')
        ->assertHasNoFormErrors();

    expect(Auth::id())->toBe($created->id);

    $this->get('/admin')->assertOk();
});

test('the admin role field only offers roles that open the admin panel', function () {
    $panel_role = Role::findOrCreate(AdminRole::CoAdmin->value, 'web');
    $other_role = Role::findOrCreate('admin', 'web');
    actingAsSuperAdmin();

    Livewire::test(CreateUser::class)
        ->assertFormFieldExists('roles', function ($field) use ($panel_role, $other_role): bool {
            $options = $field->getOptions();

            return array_key_exists($panel_role->id, $options) && ! array_key_exists($other_role->id, $options);
        })
        ->fillForm([
            'name' => 'Akun Salah Role',
            'email' => 'salah.role@example.com',
            'password' => 'rahasia-panjang-123',
            'roles' => [$other_role->id],
        ])
        ->call('create')
        ->assertHasFormErrors(['roles.0']);

    expect(User::query()->where('email', 'salah.role@example.com')->exists())->toBeFalse();
});
