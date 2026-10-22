<?php

use App\Enums\EducationLevel;
use App\Enums\LocationScope;
use App\Livewire\Scholarships\Index;
use App\Livewire\Scholarships\Show;
use App\Models\Scholarship;
use App\Models\ScholarshipApplication;
use App\Models\User;
use Illuminate\Support\Str;
use Livewire\Livewire;

beforeEach(function () {
    $this->mentee = User::factory()->profiled()->create([
        'level' => EducationLevel::S1,
        'desired_location' => LocationScope::DalamNegeri,
    ]);
});

/**
 * Create a mentee-visible scholarship with predictable attributes.
 */
function catalogScholarship(string $name, array $attributes = []): Scholarship
{
    return Scholarship::factory()->forLevel(EducationLevel::S1)->withoutRequirements()->create(array_merge([
        'scholarship_name' => $name,
        'institution' => 'Yayasan '.Str::slug($name),
        'scope' => LocationScope::DalamNegeri,
        'closing_date' => now()->addDays(30)->toDateString(),
    ], $attributes));
}

test('drafts and archived scholarships are never listed', function () {
    catalogScholarship('Beasiswa Aktif Uji');
    Scholarship::factory()->draft()->create(['scholarship_name' => 'Beasiswa Draft Rahasia']);
    Scholarship::factory()->create(['scholarship_name' => 'Beasiswa Arsip', 'status' => 'archived']);

    Livewire::actingAs($this->mentee)
        ->test(Index::class)
        ->assertSee('Beasiswa Aktif Uji')
        ->assertDontSee('Beasiswa Draft Rahasia')
        ->assertDontSee('Beasiswa Arsip');
});

test('closed scholarships are hidden by default and revealed on request', function () {
    Scholarship::factory()->closed()->create(['scholarship_name' => 'Beasiswa Ditutup Uji']);

    Livewire::actingAs($this->mentee)
        ->test(Index::class)
        ->assertDontSee('Beasiswa Ditutup Uji')
        ->set('include_closed', true)
        ->assertSee('Beasiswa Ditutup Uji');
});

test('search matches both the name and the institution', function () {
    catalogScholarship('Beasiswa Alpha Unggulan');
    catalogScholarship('Beasiswa Beta', ['institution' => 'Universitas Nusantara']);

    Livewire::actingAs($this->mentee)
        ->test(Index::class)
        ->set('search', 'Alpha')
        ->assertSee('Beasiswa Alpha Unggulan')
        ->assertDontSee('Beasiswa Beta');

    Livewire::actingAs($this->mentee)
        ->test(Index::class)
        ->set('search', 'Nusantara')
        ->assertSee('Beasiswa Beta')
        ->assertDontSee('Beasiswa Alpha Unggulan');
});

test('the level and location filters narrow the list', function () {
    catalogScholarship('Beasiswa S1 Uji', ['level' => EducationLevel::S1]);
    catalogScholarship('Beasiswa S2 Uji', ['level' => EducationLevel::S2]);

    Livewire::actingAs($this->mentee)
        ->test(Index::class)
        ->set('level', EducationLevel::S2->value)
        ->assertSee('Beasiswa S2 Uji')
        ->assertDontSee('Beasiswa S1 Uji');
});

test('the deadline sort puts the closest deadline first', function () {
    catalogScholarship('Beasiswa Lambat', ['closing_date' => now()->addDays(40)->toDateString()]);
    catalogScholarship('Beasiswa Cepat', ['closing_date' => now()->addDays(3)->toDateString()]);

    $component = Livewire::actingAs($this->mentee)->test(Index::class)->set('sort', 'deadline');

    $names = $component->viewData('scholarships')->pluck('scholarship_name')->all();

    expect($names)->toBe(['Beasiswa Cepat', 'Beasiswa Lambat']);
});

test('the catalog paginates nine scholarships per page', function () {
    collect(range(1, 12))->each(fn (int $i) => catalogScholarship('Beasiswa Halaman '.$i));

    $component = Livewire::actingAs($this->mentee)->test(Index::class);

    $page = $component->viewData('scholarships');

    expect($page->total())->toBe(12)
        ->and($page->count())->toBe(9)
        ->and($page->hasMorePages())->toBeTrue();
});

test('the detail page renders the stored scholarship fields', function () {
    $scholarship = catalogScholarship('Beasiswa Detail Uji', [
        'location' => 'Jakarta',
        'estimated_quota' => 25,
        'minimum_value' => 3.50,
        'maximum_age' => 25,
        'note' => 'Catatan penting untuk pendaftar.',
    ]);

    $this->actingAs($this->mentee)
        ->get(route('scholarships.show', $scholarship))
        ->assertOk()
        ->assertSee('Beasiswa Detail Uji')
        ->assertSee('Jakarta')
        ->assertSee('Persyaratan')
        ->assertSee('Catatan penting untuk pendaftar.');
});

test('a draft scholarship detail is not reachable', function () {
    $draft = Scholarship::factory()->draft()->create();

    $this->actingAs($this->mentee)
        ->get(route('scholarships.show', $draft))
        ->assertNotFound();
});

test('adding a scholarship from the detail page stores the selection', function () {
    $scholarship = catalogScholarship('Beasiswa Tambah Uji');

    Livewire::actingAs($this->mentee)
        ->test(Show::class, ['scholarship' => $scholarship])
        ->call('addToSelection')
        ->assertHasNoErrors();

    expect($this->mentee->scholarshipApplications()->count())->toBe(1);
});

test('the detail page enforces the five scholarship cap', function () {
    collect(range(1, 5))->each(fn (int $i) => ScholarshipApplication::factory()->create([
        'user_id' => $this->mentee->id,
        'scholarship_id' => catalogScholarship('Beasiswa Terpilih '.$i)->id,
    ]));

    $sixth = catalogScholarship('Beasiswa Keenam');

    Livewire::actingAs($this->mentee)
        ->test(Show::class, ['scholarship' => $sixth])
        ->call('addToSelection')
        ->assertHasErrors('selection');

    expect($this->mentee->scholarshipApplications()->count())->toBe(5);
});

test('an already selected scholarship shows the continue call to action', function () {
    $scholarship = catalogScholarship('Beasiswa Sudah Dipilih');
    ScholarshipApplication::factory()->create([
        'user_id' => $this->mentee->id,
        'scholarship_id' => $scholarship->id,
    ]);

    Livewire::actingAs($this->mentee)
        ->test(Show::class, ['scholarship' => $scholarship])
        ->assertSee('Lanjutkan Persiapan');
});
