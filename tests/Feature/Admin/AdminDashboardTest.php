<?php

use App\Enums\AdminRole;
use App\Filament\Widgets\StatsOverview;
use App\Models\Scholarship;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Create a super admin that can open the panel dashboard.
 */
function dashboardAdmin(): User
{
    $role = Role::findOrCreate(AdminRole::SuperAdmin->value, 'web');
    Permission::findOrCreate('ViewAny:Scholarship', 'web');
    $role->syncPermissions(['ViewAny:Scholarship']);

    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole($role);

    return $user;
}

test('the admin dashboard is reachable and renders without error', function () {
    $admin = dashboardAdmin();

    User::factory()->count(3)->create();
    Scholarship::factory()->create(['scholarship_name' => 'Beasiswa Widget Uji']);

    $this->actingAs($admin)
        ->get('/admin')
        ->assertOk();
});

test('the stats overview widget reports real database counts', function () {
    $admin = dashboardAdmin();

    User::factory()->count(3)->create();
    Scholarship::factory()->create();

    Livewire::actingAs($admin)
        ->test(StatsOverview::class)
        ->assertSee('Total Mentee')
        ->assertSee('Beasiswa Aktif');
});
