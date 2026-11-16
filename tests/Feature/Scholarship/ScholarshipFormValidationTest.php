<?php

use App\Enums\AdminRole;
use App\Filament\Resources\Scholarships\Pages\CreateScholarship;
use App\Models\Scholarship;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Validation of the Beasiswa admin form: every value the database or the
 * matching service cannot handle must be rejected with a form error instead
 * of reaching MySQL (where it would surface as a 500 error).
 */
function scholarshipFormAdmin(): User
{
    $role = Role::findOrCreate(AdminRole::SuperAdmin->value, 'web');
    Permission::findOrCreate('ViewAny:Scholarship', 'web');
    Permission::findOrCreate('Create:Scholarship', 'web');
    $role->syncPermissions(['ViewAny:Scholarship', 'Create:Scholarship']);

    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole($role);

    return $user;
}

/**
 * Minimal valid form state that individual tests override.
 *
 * @return array<string, mixed>
 */
function validScholarshipForm(array $overrides = []): array
{
    return array_merge([
        'scholarship_name' => 'Beasiswa Validasi Uji',
        'level' => 'S1',
        'status' => 'draft',
    ], $overrides);
}

test('a complete valid scholarship is created', function () {
    Livewire::actingAs(scholarshipFormAdmin())
        ->test(CreateScholarship::class)
        ->fillForm(validScholarshipForm([
            'institution' => 'Lembaga Uji',
            'location' => 'Jakarta',
            'scope' => 'dalam_negeri',
            'link' => 'https://example.com/beasiswa',
            'minimum_value' => '3.25',
            'maximum_age' => 25,
            'estimated_quota' => 100,
            'opening_date' => '2026-12-01',
            'closing_date' => '2027-01-31',
            'verification_date' => '2027-02-15',
        ]))
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Scholarship::query()->where('scholarship_name', 'Beasiswa Validasi Uji')->exists())->toBeTrue();
});

test('out of range or malformed values are rejected', function (string $field, mixed $value) {
    Livewire::actingAs(scholarshipFormAdmin())
        ->test(CreateScholarship::class)
        ->fillForm(validScholarshipForm([$field => $value]))
        ->call('create')
        ->assertHasFormErrors([$field]);

    expect(Scholarship::query()->count())->toBe(0);
})->with([
    'IPK above 4' => ['minimum_value', '4.5'],
    'IPK above column precision' => ['minimum_value', '10'],
    'negative IPK' => ['minimum_value', '-1'],
    'IPK with three decimals' => ['minimum_value', '3.255'],
    'age zero' => ['maximum_age', 0],
    'negative age' => ['maximum_age', -5],
    'age above tinyint' => ['maximum_age', 300],
    'fractional age' => ['maximum_age', '20.5'],
    'negative quota' => ['estimated_quota', -10],
    'fractional quota' => ['estimated_quota', '1.5'],
    'quota above unsigned int' => ['estimated_quota', 5000000000],
    'institution too long' => ['institution', str_repeat('a', 256)],
    'location too long' => ['location', str_repeat('a', 256)],
    'link too long' => ['link', 'https://example.com/'.str_repeat('a', 250)],
    'link without http scheme' => ['link', 'javascript://example.com/%0Aalert(1)'],
    'unknown scope' => ['scope', 'Penuh'],
    'unknown level' => ['level', 'S9'],
]);

test('the closing date cannot be before the opening date', function () {
    Livewire::actingAs(scholarshipFormAdmin())
        ->test(CreateScholarship::class)
        ->fillForm(validScholarshipForm([
            'opening_date' => '2027-02-01',
            'closing_date' => '2027-01-01',
        ]))
        ->call('create')
        ->assertHasFormErrors(['closing_date']);
});

test('the verification date cannot be before the opening date', function () {
    Livewire::actingAs(scholarshipFormAdmin())
        ->test(CreateScholarship::class)
        ->fillForm(validScholarshipForm([
            'opening_date' => '2027-02-01',
            'verification_date' => '2027-01-01',
        ]))
        ->call('create')
        ->assertHasFormErrors(['verification_date']);
});
