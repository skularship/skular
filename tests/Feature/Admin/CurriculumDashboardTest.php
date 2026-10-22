<?php

use App\Enums\AdminRole;
use App\Filament\Resources\Modules\ModuleResource;
use App\Filament\Widgets\ContentReadiness;
use App\Filament\Widgets\CurriculumOverview;
use App\Models\Module;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Create an admin that may open the curriculum dashboard.
 */
function curriculumDashboardAdmin(): User
{
    $role = Role::findOrCreate(AdminRole::SuperAdmin->value, 'web');
    Permission::findOrCreate('ViewAny:Module', 'web');
    $role->syncPermissions(['ViewAny:Module']);

    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole($role);

    return $user;
}

test('the curriculum overview describes real draft and completeness states', function () {
    $admin = curriculumDashboardAdmin();

    Module::factory()->draft()->create(['title' => 'Modul Draf Dashboard']);
    Module::factory()->create(['title' => 'Modul Terbit Dashboard']);
    Module::factory()->create([
        'title' => 'Modul Belum Lengkap Dashboard',
        'summary_material' => null,
        'inti_material' => null,
        'insights' => null,
    ]);

    Livewire::actingAs($admin)
        ->test(CurriculumOverview::class)
        ->assertSee('Modul terbit')
        ->assertSee('Modul draf')
        ->assertSee('Belum terlihat oleh mentee')
        ->assertSee('Masih ada syarat kelengkapan yang kurang');
});

test('the overview reports that nothing is pending when everything is complete', function () {
    $admin = curriculumDashboardAdmin();

    Module::factory()->create();

    Livewire::actingAs($admin)
        ->test(CurriculumOverview::class)
        ->assertSee('Tidak ada konten tertunda')
        ->assertSee('Semua modul sudah lengkap');
});

test('the content readiness board lists only draft modules and their missing parts', function () {
    $admin = curriculumDashboardAdmin();

    $draft = Module::factory()->draft()->create([
        'title' => 'Modul Draf Kurikulum',
        'summary_material' => null,
        'inti_material' => null,
        'insights' => null,
    ]);

    $published = Module::factory()->create(['title' => 'Modul Sudah Terbit']);

    Livewire::actingAs($admin)
        ->test(ContentReadiness::class)
        ->assertCanSeeTableRecords([$draft])
        ->assertCanNotSeeTableRecords([$published])
        ->assertSee('Materi Pahami tersedia');
});

test('curriculum widgets stay hidden from admins without module access', function () {
    $role = Role::findOrCreate(AdminRole::CoAdmin->value, 'web');
    Permission::findOrCreate('ViewAny:User', 'web');
    $role->syncPermissions(['ViewAny:User']);

    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole($role);

    $this->actingAs($user);

    expect(CurriculumOverview::canView())->toBeFalse()
        ->and(ContentReadiness::canView())->toBeFalse();
});

test('a curriculum staff member sees the curriculum dashboard without mentee metrics', function () {
    $role = Role::findOrCreate(AdminRole::StaffDivisi->value, 'web');

    foreach (['ViewAny:Module', 'View:Module', 'Create:Module', 'Update:Module'] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $role->syncPermissions(['ViewAny:Module', 'View:Module', 'Create:Module', 'Update:Module']);

    $staff = User::factory()->create(['email_verified_at' => now()]);
    $staff->assignRole($role);

    Module::factory()->draft()->create();

    $this->actingAs($staff)
        ->get('/admin')
        ->assertOk()
        ->assertSee('Modul draf')
        ->assertDontSee('Total Mentee');
});

test('the draft filter of the module list shows only draft modules', function () {
    $admin = curriculumDashboardAdmin();

    Module::factory()->draft()->create(['title' => 'Modul Filter Draf']);
    Module::factory()->create(['title' => 'Modul Filter Terbit']);

    $url = ModuleResource::getUrl('index', [
        'filters' => ['content_status' => ['value' => 'draft']],
    ]);

    $this->actingAs($admin)
        ->get($url)
        ->assertOk()
        ->assertSee('Modul Filter Draf')
        ->assertDontSee('Modul Filter Terbit');
});

test('the dashboard offers curriculum quick actions to module editors', function () {
    $admin = curriculumDashboardAdmin();

    $this->actingAs($admin)
        ->get('/admin')
        ->assertOk()
        ->assertSee('Buat modul baru')
        ->assertSee('Modul draf');
});
