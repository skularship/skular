<?php

use App\Enums\EducationLevel;
use App\Enums\LocationScope;
use App\Livewire\Scholarships\Recommendation;
use App\Models\Scholarship;
use App\Models\User;
use Livewire\Livewire;

test('the recommendation page only lists scholarships that fit the mentee', function () {
    $mentee = User::factory()->profiled()->create([
        'level' => EducationLevel::S1,
        'desired_location' => LocationScope::DalamNegeri,
    ]);

    Scholarship::factory()->forLevel(EducationLevel::S1)->withoutRequirements()->create([
        'scholarship_name' => 'Beasiswa Dalam Negeri Uji',
        'scope' => LocationScope::DalamNegeri,
    ]);

    Scholarship::factory()->forLevel(EducationLevel::S1)->withoutRequirements()->create([
        'scholarship_name' => 'Beasiswa Luar Negeri Uji',
        'scope' => LocationScope::LuarNegeri,
    ]);

    Livewire::actingAs($mentee)
        ->test(Recommendation::class)
        ->assertSee('Beasiswa Dalam Negeri Uji')
        ->assertDontSee('Beasiswa Luar Negeri Uji');
});

test('the empty state appears when nothing matches the profile', function () {
    $mentee = User::factory()->profiled()->create([
        'level' => EducationLevel::S2,
        'desired_location' => LocationScope::LuarNegeri,
    ]);

    Scholarship::factory()->forLevel(EducationLevel::SMA)->create([
        'scope' => LocationScope::DalamNegeri,
    ]);

    Livewire::actingAs($mentee)
        ->test(Recommendation::class)
        ->assertSee('Belum menemukan beasiswa yang cocok')
        ->assertSee('Perbarui profil');
});

test('a mentee must select at least one scholarship', function () {
    $mentee = User::factory()->profiled()->create([
        'level' => EducationLevel::S1,
        'desired_location' => LocationScope::DalamNegeri,
    ]);

    Scholarship::factory()->forLevel(EducationLevel::S1)->withoutRequirements()->create([
        'scope' => LocationScope::DalamNegeri,
    ]);

    Livewire::actingAs($mentee)
        ->test(Recommendation::class)
        ->set('selected', [])
        ->call('confirmSelection')
        ->assertHasErrors('selected');

    expect($mentee->scholarshipApplications()->count())->toBe(0);
});

test('confirming a selection stores the applications with a primary choice', function () {
    $mentee = User::factory()->profiled()->create([
        'level' => EducationLevel::S1,
        'desired_location' => LocationScope::DalamNegeri,
    ]);

    $first = Scholarship::factory()->forLevel(EducationLevel::S1)->withoutRequirements()->create([
        'scope' => LocationScope::DalamNegeri,
    ]);

    $second = Scholarship::factory()->forLevel(EducationLevel::S1)->withoutRequirements()->create([
        'scope' => LocationScope::DalamNegeri,
    ]);

    Livewire::actingAs($mentee)
        ->test(Recommendation::class)
        ->set('selected', [$first->id, $second->id])
        ->call('makePrimary', $second->id)
        ->call('confirmSelection')
        ->assertRedirect(route('dashboard'));

    expect($mentee->scholarshipApplications()->count())->toBe(2)
        ->and($mentee->scholarshipApplications()->where('is_primary', true)->value('scholarship_id'))->toBe($second->id);
});

test('the same scholarship cannot be selected twice', function () {
    $mentee = User::factory()->profiled()->create([
        'level' => EducationLevel::S1,
        'desired_location' => LocationScope::DalamNegeri,
    ]);

    $scholarship = Scholarship::factory()->forLevel(EducationLevel::S1)->withoutRequirements()->create([
        'scope' => LocationScope::DalamNegeri,
    ]);

    Livewire::actingAs($mentee)
        ->test(Recommendation::class)
        ->set('selected', [$scholarship->id])
        ->call('confirmSelection');

    Livewire::actingAs($mentee)
        ->test(Recommendation::class)
        ->set('selected', [$scholarship->id])
        ->call('confirmSelection');

    expect($mentee->scholarshipApplications()->count())->toBe(1);
});

test('a mentee can change which scholarship is primary', function () {
    $mentee = User::factory()->profiled()->create([
        'level' => EducationLevel::S1,
        'desired_location' => LocationScope::DalamNegeri,
    ]);

    $first = Scholarship::factory()->forLevel(EducationLevel::S1)->withoutRequirements()->create([
        'scope' => LocationScope::DalamNegeri,
    ]);

    $second = Scholarship::factory()->forLevel(EducationLevel::S1)->withoutRequirements()->create([
        'scope' => LocationScope::DalamNegeri,
    ]);

    Livewire::actingAs($mentee)
        ->test(Recommendation::class)
        ->set('selected', [$first->id, $second->id])
        ->call('makePrimary', $first->id)
        ->call('confirmSelection');

    Livewire::actingAs($mentee)
        ->test(Recommendation::class)
        ->call('makePrimary', $second->id)
        ->call('confirmSelection');

    $mentee->refresh();

    expect($mentee->scholarshipApplications()->where('is_primary', true)->value('scholarship_id'))->toBe($second->id)
        ->and($mentee->scholarshipApplications()->where('is_primary', true)->count())->toBe(1);
});

test('a scholarship whose registration window has closed cannot be selected', function () {
    $mentee = User::factory()->profiled()->create([
        'level' => EducationLevel::S1,
        'desired_location' => LocationScope::DalamNegeri,
    ]);

    $expired = Scholarship::factory()->forLevel(EducationLevel::S1)->withoutRequirements()->create([
        'scope' => LocationScope::DalamNegeri,
        'closing_date' => now()->subDay(),
    ]);

    Livewire::actingAs($mentee)
        ->test(Recommendation::class)
        ->set('selected', [$expired->id])
        ->call('confirmSelection')
        ->assertHasErrors('selected');

    expect($mentee->scholarshipApplications()->count())->toBe(0);
});

test('only active scholarships can be selected', function () {
    $mentee = User::factory()->profiled()->create([
        'level' => EducationLevel::S1,
        'desired_location' => LocationScope::DalamNegeri,
    ]);

    $draft = Scholarship::factory()->forLevel(EducationLevel::S1)->draft()->create([
        'scope' => LocationScope::DalamNegeri,
    ]);

    Livewire::actingAs($mentee)
        ->test(Recommendation::class)
        ->set('selected', [$draft->id])
        ->call('confirmSelection')
        ->assertHasErrors('selected');
});
