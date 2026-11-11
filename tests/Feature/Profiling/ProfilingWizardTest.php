<?php

use App\Enums\EducationLevel;
use App\Enums\LocationScope;
use App\Livewire\Profiling\Wizard;
use App\Models\Cluster;
use App\Models\Scholarship;
use App\Models\User;
use Livewire\Livewire;

/**
 * A complete, valid profiling batch as the browser sends it.
 *
 * @return array<string, string>
 */
function validProfilingAnswers(int $cluster_id): array
{
    return [
        'level' => 'S1',
        'age' => '21',
        'gpa' => '3.75',
        'has_sktm' => '1',
        'desired_level' => 'S2',
        'desired_location' => 'luar_negeri',
        'cluster' => (string) $cluster_id,
        'phone' => '081234567890',
    ];
}

test('a guest is redirected to login before profiling', function () {
    $this->get(route('profiling'))->assertRedirect(route('login'));
});

test('unverified mentees cannot open the profiling wizard', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('profiling'))
        ->assertRedirect(route('verification.notice'));
});

test('the wizard starts on the first question', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Wizard::class)
        ->assertSet('step', 1)
        ->assertSee('Jenjang');
});

test('the wizard navigates and answers on the client without step endpoints', function () {
    expect(method_exists(Wizard::class, 'next'))->toBeFalse()
        ->and(method_exists(Wizard::class, 'goBack'))->toBeFalse()
        ->and(method_exists(Wizard::class, 'goToStep'))->toBeFalse()
        ->and(method_exists(Wizard::class, 'selectChoice'))->toBeFalse()
        ->and(method_exists(Wizard::class, 'finish'))->toBeFalse();
});

test('the location step only offers domestic and international study destinations', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('profiling'))
        ->assertOk()
        ->assertSee('Preferensi lokasi beasiswa')
        ->assertSee('data-test="option-desired-location-dalam_negeri"', false)
        ->assertSee('data-test="option-desired-location-luar_negeri"', false)
        ->assertDontSee('data-test="option-desired-location-Tunjangan saja"', false)
        ->assertDontSee('data-test="option-desired-location-Parsial"', false)
        ->assertDontSee('data-test="option-desired-location-Penuh"', false);
});

test('a funding category cannot be submitted as a location preference', function () {
    $cluster = Cluster::factory()->create();
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Wizard::class)
        ->call('submit', [...validProfilingAnswers($cluster->id), 'desired_location' => 'Penuh'])
        ->assertReturned(fn (array $result): bool => $result['ok'] === false && $result['step'] === 6)
        ->assertHasErrors(['desired_location']);

    expect($user->refresh()->profile_completed_at)->toBeNull();
});

test('finishing the wizard shows matching scholarships on the recommendation page', function () {
    $cluster = Cluster::factory()->create();
    $user = User::factory()->create();

    $domestic = Scholarship::factory()->forLevel(EducationLevel::S1)->withoutRequirements()->create([
        'scholarship_name' => 'Beasiswa Domestik Pasca Wizard',
        'scope' => LocationScope::DalamNegeri,
    ]);

    Scholarship::factory()->forLevel(EducationLevel::S1)->withoutRequirements()->create([
        'scholarship_name' => 'Beasiswa Internasional Pasca Wizard',
        'scope' => LocationScope::LuarNegeri,
    ]);

    Livewire::actingAs($user)
        ->test(Wizard::class)
        ->call('submit', [...validProfilingAnswers($cluster->id), 'desired_location' => 'dalam_negeri'])
        ->assertHasNoErrors()
        ->assertRedirect(route('profiling.recommendation'));

    expect($user->refresh()->desired_location)->toBe(LocationScope::DalamNegeri);

    $this->actingAs($user)
        ->get(route('profiling.recommendation'))
        ->assertOk()
        ->assertSee('Beasiswa Domestik Pasca Wizard')
        ->assertDontSee('Beasiswa Internasional Pasca Wizard')
        ->assertSee('Lihat detail');
});

test('every single choice question renders one radio group bound to frontend state', function () {
    $cluster = Cluster::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('profiling'))
        ->assertOk()
        ->assertSee('x-model="answers.level"', false)
        ->assertSee('name="answers.level"', false)
        ->assertSee('x-model="answers.desired_location"', false)
        ->assertSee('x-model="answers.has_sktm"', false)
        ->assertSee('x-model="answers.cluster"', false)
        ->assertSee('type="radio"', false)
        ->assertSee('data-test="option-cluster-'.$cluster->id.'"', false)
        ->assertDontSee('$wire.set', false)
        ->assertDontSee('wire:model="level"', false);
});

test('answering a question does not write anything to the profile', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)->test(Wizard::class);

    expect($user->refresh()->level)->toBeNull()
        ->and($user->age)->toBeNull()
        ->and($user->profile_completed_at)->toBeNull();
});

test('finishing the wizard persists the whole batch and opens the recommendations', function () {
    $cluster = Cluster::factory()->create();
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Wizard::class)
        ->call('submit', validProfilingAnswers($cluster->id))
        ->assertHasNoErrors()
        ->assertRedirect(route('profiling.recommendation'));

    $user->refresh();

    expect($user->level->value)->toBe('S1')
        ->and($user->age)->toBe(21)
        ->and((float) $user->gpa)->toBe(3.75)
        ->and($user->has_sktm)->toBeTrue()
        ->and($user->desired_level->value)->toBe('S2')
        ->and($user->desired_location->value)->toBe('luar_negeri')
        ->and($user->cluster_id)->toBe($cluster->id)
        ->and($user->phone)->toBe('081234567890')
        ->and($user->hasCompletedProfile())->toBeTrue()
        ->and(session('profiling_level'))->toBeNull();
});

test('an incomplete batch is rejected and points at the first missing question', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Wizard::class)
        ->call('submit', [])
        ->assertReturned(fn (array $result): bool => $result['ok'] === false && $result['step'] === 1)
        ->assertHasErrors(['level']);

    expect($user->refresh()->profile_completed_at)->toBeNull();
});

test('the age question enforces the allowed range on the final batch', function () {
    $cluster = Cluster::factory()->create();
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Wizard::class)
        ->call('submit', [...validProfilingAnswers($cluster->id), 'age' => '14'])
        ->assertReturned(fn (array $result): bool => $result['ok'] === false && $result['step'] === 2)
        ->assertHasErrors(['age']);

    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Wizard::class)
        ->call('submit', [...validProfilingAnswers($cluster->id), 'age' => '61'])
        ->assertHasErrors(['age']);

    expect($user->refresh()->profile_completed_at)->toBeNull();
});

test('the gpa question rejects values outside the four point scale', function () {
    $cluster = Cluster::factory()->create();
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Wizard::class)
        ->call('submit', [...validProfilingAnswers($cluster->id), 'gpa' => '4.5'])
        ->assertReturned(fn (array $result): bool => $result['ok'] === false && $result['step'] === 3)
        ->assertHasErrors(['gpa']);
});

test('the phone question only accepts digits, plus, dash and spaces', function () {
    $cluster = Cluster::factory()->create();
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Wizard::class)
        ->call('submit', [...validProfilingAnswers($cluster->id), 'phone' => 'nomor saya 0812'])
        ->assertReturned(fn (array $result): bool => $result['ok'] === false && $result['step'] === 8)
        ->assertHasErrors(['phone']);
});

test('an option that is not offered by the backend is rejected', function () {
    $cluster = Cluster::factory()->create();
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Wizard::class)
        ->call('submit', [...validProfilingAnswers($cluster->id), 'level' => 'sarjana'])
        ->assertReturned(fn (array $result): bool => $result['ok'] === false && $result['step'] === 1)
        ->assertHasErrors(['level']);

    expect($user->refresh()->profile_completed_at)->toBeNull();
});

test('an inactive cluster cannot be selected', function () {
    $cluster = Cluster::factory()->inactive()->create();
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Wizard::class)
        ->call('submit', validProfilingAnswers($cluster->id))
        ->assertReturned(fn (array $result): bool => $result['ok'] === false && $result['step'] === 7)
        ->assertHasErrors(['cluster']);
});

test('the sktm answer maps the card value onto the stored boolean', function () {
    $cluster = Cluster::factory()->create();
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Wizard::class)
        ->call('submit', [...validProfilingAnswers($cluster->id), 'has_sktm' => '0'])
        ->assertHasNoErrors();

    expect($user->refresh()->has_sktm)->toBeFalse();
});

test('extra keys in the batch are ignored and cannot overwrite the profile', function () {
    $cluster = Cluster::factory()->create();
    $other = Cluster::factory()->create();
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Wizard::class)
        ->call('submit', [
            ...validProfilingAnswers($cluster->id),
            'cluster_id' => (string) $other->id,
            'profile_completed_at' => '2000-01-01 00:00:00',
        ])
        ->assertHasNoErrors();

    $user->refresh();

    expect($user->cluster_id)->toBe($cluster->id)
        ->and($user->profile_completed_at?->year)->toBe(now()->year);
});

test('submitting the same batch twice does not create duplicate data', function () {
    $cluster = Cluster::factory()->create();
    $user = User::factory()->create();
    $answers = validProfilingAnswers($cluster->id);

    $component = Livewire::actingAs($user)->test(Wizard::class);

    $component->call('submit', $answers)->assertHasNoErrors();
    $component->call('submit', $answers)->assertHasNoErrors();

    expect(User::query()->count())->toBe(1)
        ->and($user->refresh()->cluster_id)->toBe($cluster->id)
        ->and($user->hasCompletedProfile())->toBeTrue();
});

test('a returning mentee sees their stored answers pre-filled', function () {
    $cluster = Cluster::factory()->create();
    $user = User::factory()->profiled()->create([
        'level' => 'S1',
        'age' => 22,
        'cluster_id' => $cluster->id,
    ]);

    Livewire::actingAs($user)
        ->test(Wizard::class)
        ->assertSet('answers.level', 'S1')
        ->assertSet('answers.age', 22)
        ->assertSet('answers.cluster', (string) $cluster->id);
});

test('a rejected batch writes nothing and can be retried without losing the profile', function () {
    $cluster = Cluster::factory()->create();
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)->test(Wizard::class);

    $component
        ->call('submit', [...validProfilingAnswers($cluster->id), 'phone' => 'nomor saya'])
        ->assertHasErrors(['phone'])
        ->assertSet('answers.level', null)
        ->assertSet('step', 1);

    expect($user->refresh()->profile_completed_at)->toBeNull();

    $component
        ->call('submit', validProfilingAnswers($cluster->id))
        ->assertHasNoErrors()
        ->assertRedirect(route('profiling.recommendation'));

    expect($user->refresh()->hasCompletedProfile())->toBeTrue();
});
