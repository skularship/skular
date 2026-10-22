<?php

use App\Enums\AdminRole;
use App\Filament\Resources\Scholarships\Pages\CreateScholarship;
use App\Models\Scholarship;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Create a super admin able to use the scholarship admin form.
 */
function scholarshipAdmin(): User
{
    $role = Role::findOrCreate(AdminRole::SuperAdmin->value, 'web');
    Permission::findOrCreate('ViewAny:Scholarship', 'web');
    $role->syncPermissions(['ViewAny:Scholarship']);

    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole($role);

    return $user;
}

test('key benefits and values sought are stored and read as arrays', function () {
    $scholarship = Scholarship::factory()->create([
        'key_benefits' => ['Biaya kuliah penuh', 'Tunjangan biaya hidup'],
        'values_sought' => ['Kepemimpinan', 'Integritas', 'Kontribusi sosial'],
    ]);

    $fresh = $scholarship->fresh();

    expect($fresh->key_benefits)->toBeArray()
        ->and($fresh->key_benefits)->toBe(['Biaya kuliah penuh', 'Tunjangan biaya hidup'])
        ->and($fresh->values_sought)->toBe(['Kepemimpinan', 'Integritas', 'Kontribusi sosial'])
        ->and($fresh->hasKeyBenefits())->toBeTrue()
        ->and($fresh->valuesSought())->toHaveCount(3);
});

test('empty lists are normalised and never break the page', function () {
    $scholarship = Scholarship::factory()->create([
        'key_benefits' => [],
        'values_sought' => null,
    ]);

    expect($scholarship->keyBenefits())->toBe([])
        ->and($scholarship->valuesSought())->toBe([])
        ->and($scholarship->hasKeyBenefits())->toBeFalse()
        ->and($scholarship->hasValuesSought())->toBeFalse();
});

test('blank entries are trimmed away', function () {
    $scholarship = Scholarship::factory()->create([
        'key_benefits' => ['  Biaya penuh  ', '', '   '],
    ]);

    expect($scholarship->keyBenefits())->toBe(['Biaya penuh']);
});

test('the detail page shows the benefit and values sections', function () {
    $mentee = User::factory()->profiled()->create();
    $scholarship = Scholarship::factory()->create([
        'scholarship_name' => 'Beasiswa Benefit Uji',
        'key_benefits' => ['Biaya kuliah penuh', 'Program mentoring'],
        'values_sought' => ['Kepemimpinan', 'Kontribusi sosial'],
    ]);

    $this->actingAs($mentee)
        ->get(route('scholarships.show', $scholarship))
        ->assertOk()
        ->assertSee('Benefit Utama')
        ->assertSee('Biaya kuliah penuh')
        ->assertSee('Nilai yang Dicari')
        ->assertSee('Kepemimpinan');
});

test('the detail page hides empty benefit and values panels', function () {
    $mentee = User::factory()->profiled()->create();
    $scholarship = Scholarship::factory()->create([
        'key_benefits' => [],
        'values_sought' => null,
    ]);

    $this->actingAs($mentee)
        ->get(route('scholarships.show', $scholarship))
        ->assertOk()
        ->assertDontSee('Benefit Utama')
        ->assertDontSee('Nilai yang Dicari');
});

test('the admin form stores the repeater items as a plain array', function () {
    $admin = scholarshipAdmin();

    Livewire::actingAs($admin)
        ->test(CreateScholarship::class)
        ->fillForm([
            'scholarship_name' => 'Beasiswa Repeater Uji',
            'level' => 's1',
            'status' => 'active',
            'key_benefits' => [['value' => 'Biaya kuliah penuh'], ['value' => 'Tunjangan biaya hidup']],
            'values_sought' => [['value' => 'Kepemimpinan'], ['value' => 'Integritas']],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $scholarship = Scholarship::query()->where('scholarship_name', 'Beasiswa Repeater Uji')->first();

    expect($scholarship)->not->toBeNull()
        ->and($scholarship->key_benefits)->toBe(['Biaya kuliah penuh', 'Tunjangan biaya hidup'])
        ->and($scholarship->values_sought)->toBe(['Kepemimpinan', 'Integritas']);
});
