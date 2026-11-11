<?php

use App\Contracts\ScholarshipImageServiceInterface;
use App\Contracts\ScholarshipMatchingServiceInterface;
use App\Data\UserProfileData;
use App\Enums\AdminRole;
use App\Enums\EducationLevel;
use App\Enums\LocationScope;
use App\Filament\Resources\Scholarships\Pages\EditScholarship;
use App\Livewire\Scholarships\Recommendation;
use App\Livewire\Scholarships\Show;
use App\Models\Scholarship;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/**
 * Create a profiled S1 mentee who prefers domestic study.
 */
function cardMentee(): User
{
    return User::factory()->profiled()->create([
        'level' => EducationLevel::S1,
        'desired_location' => LocationScope::DalamNegeri,
    ]);
}

/**
 * Create an open scholarship that the card mentee matches.
 *
 * @param  array<string, mixed>  $attributes
 */
function cardScholarship(array $attributes = []): Scholarship
{
    return Scholarship::factory()->forLevel(EducationLevel::S1)->withoutRequirements()->create([
        'scope' => LocationScope::DalamNegeri,
        ...$attributes,
    ]);
}

test('a scholarship card shows its lazy loaded image', function () {
    $scholarship = cardScholarship(['scholarship_name' => 'Beasiswa Bergambar']);
    $scholarship->forceFill(['image_path' => 'scholarships/kartu.webp'])->saveQuietly();

    Livewire::actingAs(cardMentee())
        ->test(Recommendation::class)
        ->assertSee('Beasiswa Bergambar')
        ->assertSeeHtml('src="'.Storage::disk('public')->url('scholarships/kartu.webp').'"')
        ->assertSeeHtml('loading="lazy"')
        ->assertDontSeeHtml('data-test="scholarship-image-placeholder"');
});

test('a scholarship card without an image shows the branded placeholder', function () {
    cardScholarship(['scholarship_name' => 'Beasiswa Tanpa Gambar']);

    Livewire::actingAs(cardMentee())
        ->test(Recommendation::class)
        ->assertSee('Beasiswa Tanpa Gambar')
        ->assertSeeHtml('data-test="scholarship-image-placeholder"');
});

test('the recommendation card shows the match label but never the numeric score', function () {
    $mentee = cardMentee();
    // An unmet GPA plus a met age limit gives a distinctive partial score.
    $scholarship = cardScholarship(['minimum_value' => 3.5, 'maximum_age' => 30]);
    $mentee->update(['gpa' => 2.0, 'age' => 22]);

    $match = app(ScholarshipMatchingServiceInterface::class)
        ->matchOne(UserProfileData::fromUser($mentee->fresh()), $scholarship->fresh());

    expect($match->score)->toBeGreaterThan(5)->toBeLessThan(100);

    Livewire::actingAs($mentee->fresh())
        ->test(Recommendation::class)
        ->assertSeeText($match->tier->label())
        ->assertDontSeeText($match->tier->label().' · ')
        ->assertDontSeeText((string) $match->score);
});

test('a closed scholarship card cannot be selected or made primary', function () {
    cardScholarship(['scholarship_name' => 'Beasiswa Tutup', 'closing_date' => now()->subDay()]);

    Livewire::actingAs(cardMentee())
        ->test(Recommendation::class)
        ->assertSeeText('Ditutup')
        ->assertSeeHtml('disabled')
        ->assertDontSeeHtml('data-test="make-primary"');
});

test('making a sixth scholarship primary is refused', function () {
    $mentee = cardMentee();
    $scholarships = collect(range(1, 6))->map(fn (): Scholarship => cardScholarship());
    $selected = $scholarships->take(5)->pluck('id')->all();

    Livewire::actingAs($mentee)
        ->test(Recommendation::class)
        ->set('selected', $selected)
        ->call('makePrimary', $scholarships->last()->id)
        ->assertHasErrors('selected')
        ->assertSet('primary', null)
        ->assertSet('selected', $selected);
});

test('the detail page shows the match label and reasons for a profiled mentee', function () {
    $scholarship = cardScholarship();

    Livewire::actingAs(cardMentee())
        ->test(Show::class, ['scholarship' => $scholarship])
        ->assertSeeHtml('data-test="match-reasons"')
        ->assertSeeText('Kecocokan dengan profilmu');
});

test('the detail page hides match reasons for a scholarship outside the mentee level', function () {
    $scholarship = Scholarship::factory()->forLevel(EducationLevel::S3)->create();

    Livewire::actingAs(cardMentee())
        ->test(Show::class, ['scholarship' => $scholarship])
        ->assertDontSeeHtml('data-test="match-reasons"');
});

test('a stored card image is downscaled to a small webp', function () {
    Storage::fake('public');

    $path = app(ScholarshipImageServiceInterface::class)
        ->store(UploadedFile::fake()->image('foto.jpg', 2000, 1000));

    Storage::disk('public')->assertExists($path);

    [$width, $height] = getimagesizefromstring((string) Storage::disk('public')->get($path));

    expect($path)->toStartWith('scholarships/')->toEndWith('.webp')
        ->and($width)->toBe(512)
        ->and($height)->toBe(256);
});

test('replacing a card image removes the previous file', function () {
    Storage::fake('public');
    Storage::disk('public')->put('scholarships/lama.webp', 'x');
    Storage::disk('public')->put('scholarships/baru.webp', 'y');

    $scholarship = cardScholarship(['image_path' => 'scholarships/lama.webp']);
    $scholarship->update(['image_path' => 'scholarships/baru.webp']);

    Storage::disk('public')->assertMissing('scholarships/lama.webp');
    Storage::disk('public')->assertExists('scholarships/baru.webp');
});

test('the admin form rejects a card image larger than 5 MB', function () {
    Storage::fake('public');

    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole(Role::findOrCreate(AdminRole::SuperAdmin->value, 'web'));
    $scholarship = cardScholarship();

    Livewire::actingAs($admin)
        ->test(EditScholarship::class, ['record' => $scholarship->getRouteKey()])
        ->fillForm(['image_path' => UploadedFile::fake()->image('besar.jpg')->size(6000)])
        ->call('save')
        ->assertHasFormErrors(['image_path']);

    expect($scholarship->fresh()->image_path)->toBeNull();
});

test('the admin form stores an accepted card image as a resized webp', function () {
    Storage::fake('public');

    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole(Role::findOrCreate(AdminRole::SuperAdmin->value, 'web'));
    $scholarship = cardScholarship();

    Livewire::actingAs($admin)
        ->test(EditScholarship::class, ['record' => $scholarship->getRouteKey()])
        ->fillForm(['image_path' => UploadedFile::fake()->image('kartu.png', 1200, 1200)])
        ->call('save')
        ->assertHasNoFormErrors();

    $path = $scholarship->fresh()->image_path;

    expect($path)->toEndWith('.webp');
    Storage::disk('public')->assertExists($path);
});
