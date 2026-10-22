<?php

use App\Contracts\ScholarshipApplicationServiceInterface;
use App\Enums\EducationLevel;
use App\Enums\LocationScope;
use App\Livewire\Scholarships\Recommendation;
use App\Models\Scholarship;
use App\Models\ScholarshipApplication;
use App\Models\User;
use App\Services\ScholarshipApplicationService;
use Livewire\Livewire;

beforeEach(function () {
    $this->mentee = User::factory()->profiled()->create([
        'level' => EducationLevel::S1,
        'desired_location' => LocationScope::DalamNegeri,
    ]);

    $this->service = app(ScholarshipApplicationServiceInterface::class);
});

/**
 * Create an open, matchable scholarship for the profiled mentee.
 */
function openScholarship(string $name, ?string $closing = null): Scholarship
{
    return Scholarship::factory()->forLevel(EducationLevel::S1)->withoutRequirements()->create([
        'scholarship_name' => $name,
        'scope' => LocationScope::DalamNegeri,
        'closing_date' => $closing ?? now()->addDays(30)->toDateString(),
    ]);
}

test('a mentee may select up to five scholarships', function () {
    $ids = collect(range(1, 6))->map(fn (int $i): int => openScholarship('Beasiswa '.$i)->id)->all();

    $added = $this->service->select($this->mentee, array_slice($ids, 0, 5));

    expect($added)->toBe(5)
        ->and($this->service->selectedCount($this->mentee))->toBe(ScholarshipApplicationService::MAX_SELECTIONS)
        ->and($this->service->hasReachedLimit($this->mentee))->toBeTrue()
        ->and($this->service->remainingSlots($this->mentee))->toBe(0);
});

test('the sixth scholarship is rejected by the backend', function () {
    $ids = collect(range(1, 6))->map(fn (int $i): int => openScholarship('Beasiswa '.$i)->id)->all();

    $this->service->select($this->mentee, array_slice($ids, 0, 5));

    $added = $this->service->select($this->mentee, [$ids[5]]);

    expect($added)->toBe(0)
        ->and($this->service->selectedCount($this->mentee))->toBe(5)
        ->and($this->mentee->scholarshipApplications()->where('scholarship_id', $ids[5])->exists())->toBeFalse();
});

test('a single request that asks for more than five only stores five', function () {
    $ids = collect(range(1, 7))->map(fn (int $i): int => openScholarship('Beasiswa '.$i)->id)->all();

    $added = $this->service->select($this->mentee, $ids);

    expect($added)->toBe(5)
        ->and($this->service->selectedCount($this->mentee))->toBe(5);
});

test('duplicate selections are never stored twice', function () {
    $scholarship = openScholarship('Beasiswa Duplikat');

    $this->service->select($this->mentee, [$scholarship->id]);
    $this->service->select($this->mentee, [$scholarship->id]);

    expect($this->mentee->scholarshipApplications()->count())->toBe(1);
});

test('the recommendation page blocks a sixth pick with a clear message', function () {
    collect(range(1, 6))->each(fn (int $i) => openScholarship('Beasiswa '.$i));

    $ids = Scholarship::query()->pluck('id')->all();

    Livewire::actingAs($this->mentee)
        ->test(Recommendation::class)
        ->set('selected', $ids)
        ->call('confirmSelection')
        ->assertHasErrors('selected');

    expect($this->mentee->scholarshipApplications()->count())->toBe(0);
});

test('applications are ordered by the closest deadline with nulls last', function () {
    $later = openScholarship('Beasiswa Lambat', now()->addDays(30)->toDateString());
    $soon = openScholarship('Beasiswa Segera', now()->addDays(5)->toDateString());
    $none = openScholarship('Beasiswa Tanpa Deadline', now()->addDays(90)->toDateString());
    $none->update(['closing_date' => null]);

    $this->service->select($this->mentee, [$later->id, $soon->id, $none->id]);

    $order = $this->service->orderedApplications($this->mentee)
        ->map(fn (ScholarshipApplication $application): string => $application->scholarship->scholarship_name)
        ->all();

    expect($order)->toBe(['Beasiswa Segera', 'Beasiswa Lambat', 'Beasiswa Tanpa Deadline']);
});

test('the active application is the closest unfinished scholarship', function () {
    $first = openScholarship('Beasiswa Pertama', now()->addDays(3)->toDateString());
    $second = openScholarship('Beasiswa Kedua', now()->addDays(20)->toDateString());

    $this->service->select($this->mentee, [$second->id, $first->id], $first->id);

    expect($this->service->activeApplication($this->mentee)?->scholarship_id)->toBe($first->id)
        ->and($this->service->nextApplication($this->mentee)?->scholarship_id)->toBe($second->id);
});

test('an expired scholarship cannot be selected', function () {
    $expired = Scholarship::factory()->forLevel(EducationLevel::S1)->withoutRequirements()->create([
        'scope' => LocationScope::DalamNegeri,
        'closing_date' => now()->subDay()->toDateString(),
    ]);

    expect($this->service->unavailableIds([$expired->id]))->toBe([$expired->id])
        ->and($this->service->select($this->mentee, [$expired->id]))->toBe(0);
});

test('a draft scholarship cannot be selected', function () {
    $draft = Scholarship::factory()->forLevel(EducationLevel::S1)->draft()->create([
        'scope' => LocationScope::DalamNegeri,
    ]);

    expect($this->service->select($this->mentee, [$draft->id]))->toBe(0);
    expect($this->mentee->scholarshipApplications()->count())->toBe(0);
});
