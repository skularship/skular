<?php

use App\Livewire\Profiling\Wizard;
use App\Models\Cluster;
use App\Models\User;
use Livewire\Livewire;

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

test('an answered step is stored as a session draft before moving on', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Wizard::class)
        ->set('level', 's1')
        ->call('next')
        ->assertHasNoErrors()
        ->assertSet('step', 2);

    expect(session('profiling_level'))->toBe('s1');
});

test('the next question replaces the one just answered', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Wizard::class)
        ->assertSee('Pilih jenjang pendidikanmu saat ini')
        ->set('level', 's1')
        ->call('next')
        ->assertSet('step', 2)
        ->assertSee('Berapa usiamu sekarang?')
        ->assertDontSee('Pilih jenjang pendidikanmu saat ini')
        ->call('goBack')
        ->assertSet('step', 1)
        ->assertSee('Pilih jenjang pendidikanmu saat ini')
        ->assertDontSee('Berapa usiamu sekarang?');
});

test('the stepper jumps straight to another question', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Wizard::class)
        ->call('goToStep', 5)
        ->assertSet('step', 5)
        ->assertSee('Jenjang apa yang ingin kamu kejar?')
        ->assertDontSee('Pilih jenjang pendidikanmu saat ini')
        ->call('goToStep', 99)
        ->assertSet('step', 8);
});

test('a step cannot be skipped without an answer', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Wizard::class)
        ->call('next')
        ->assertHasErrors(['level' => 'required'])
        ->assertSet('step', 1);
});

test('the age question enforces the allowed range', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Wizard::class)
        ->set('step', 2)
        ->set('age', '14')
        ->call('next')
        ->assertHasErrors(['age' => 'min']);

    Livewire::actingAs($user)
        ->test(Wizard::class)
        ->set('step', 2)
        ->set('age', '61')
        ->call('next')
        ->assertHasErrors(['age' => 'max']);
});

test('the gpa question rejects values outside the four point scale', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Wizard::class)
        ->set('step', 3)
        ->set('gpa', '4.5')
        ->call('next')
        ->assertHasErrors(['gpa' => 'max']);
});

test('the phone question only accepts digits, plus, dash and spaces', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Wizard::class)
        ->set('step', 8)
        ->set('phone', 'nomor saya 0812')
        ->call('next')
        ->assertHasErrors(['phone' => 'regex']);
});

test('finishing the wizard persists the profile and opens the recommendations', function () {
    $cluster = Cluster::factory()->create();
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Wizard::class)
        ->set('step', 8)
        ->set('level', 's1')
        ->set('age', '21')
        ->set('gpa', '3.75')
        ->set('has_sktm', true)
        ->set('desired_level', 's2')
        ->set('desired_location', 'luar_negeri')
        ->set('cluster', (string) $cluster->id)
        ->set('phone', '081234567890')
        ->call('finish')
        ->assertHasNoErrors()
        ->assertRedirect(route('profiling.recommendation'));

    $user->refresh();

    expect($user->level->value)->toBe('s1')
        ->and($user->age)->toBe(21)
        ->and((float) $user->gpa)->toBe(3.75)
        ->and($user->has_sktm)->toBeTrue()
        ->and($user->desired_level->value)->toBe('s2')
        ->and($user->desired_location->value)->toBe('luar_negeri')
        ->and($user->cluster_id)->toBe($cluster->id)
        ->and($user->phone)->toBe('081234567890')
        ->and($user->hasCompletedProfile())->toBeTrue()
        ->and(session('profiling_level'))->toBeNull();
});

test('finishing with a missing answer reports the error and returns to that step', function () {
    $cluster = Cluster::factory()->create();
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Wizard::class)
        ->set('step', 8)
        ->set('level', 's1')
        ->set('age', '21')
        ->set('gpa', '3.75')
        ->set('has_sktm', false)
        ->set('desired_level', 's1')
        ->set('desired_location', 'dalam_negeri')
        ->set('cluster', (string) $cluster->id)
        ->call('finish')
        ->assertHasErrors(['phone' => 'required'])
        ->assertSet('step', 8);

    expect($user->refresh()->profile_completed_at)->toBeNull();
});

test('an inactive cluster cannot be selected', function () {
    $cluster = Cluster::factory()->inactive()->create();
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Wizard::class)
        ->set('step', 7)
        ->set('cluster', (string) $cluster->id)
        ->call('next')
        ->assertHasErrors(['cluster' => 'exists']);
});

test('a returning mentee sees their stored answers pre-filled', function () {
    $cluster = Cluster::factory()->create();
    $user = User::factory()->profiled()->create([
        'level' => 's1',
        'age' => 22,
        'cluster_id' => $cluster->id,
    ]);

    Livewire::actingAs($user)
        ->test(Wizard::class)
        ->assertSet('level', 's1')
        ->assertSet('age', '22')
        ->assertSet('cluster', (string) $cluster->id);
});

test('selecting a card releases the previous answer on single-choice steps', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Wizard::class)
        ->set('level', 'sma')
        ->call('selectChoice', 'level', 's1')
        ->assertSet('level', 's1')
        ->call('selectChoice', 'level', 'smk')
        ->assertSet('level', 'smk');
});

test('tapping the active card clears it and shows the required error again', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Wizard::class)
        ->set('step', 6)
        ->set('desired_location', 'dalam_negeri')
        ->call('selectChoice', 'desired_location', 'dalam_negeri')
        ->assertSet('desired_location', null)
        ->call('next')
        ->assertHasErrors(['desired_location']);
});

test('selecting a card clears the validation error immediately', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Wizard::class)
        ->call('next')
        ->assertHasErrors(['level' => 'required'])
        ->call('selectChoice', 'level', 's1')
        ->assertHasNoErrors();
});

test('the sktm card toggles a boolean answer', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Wizard::class)
        ->assertSet('has_sktm', null)
        ->call('selectChoice', 'has_sktm', '1')
        ->assertSet('has_sktm', true)
        ->call('selectChoice', 'has_sktm', '0')
        ->assertSet('has_sktm', false)
        ->call('selectChoice', 'has_sktm', '0')
        ->assertSet('has_sktm', null);
});

test('enter on the last step submits the whole profile like the finish button', function () {
    $cluster = Cluster::factory()->create();
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Wizard::class)
        ->set('step', 8)
        ->set('level', 's1')
        ->set('age', '21')
        ->set('gpa', '3.75')
        ->set('has_sktm', true)
        ->set('desired_level', 's2')
        ->set('desired_location', 'luar_negeri')
        ->set('cluster', (string) $cluster->id)
        ->set('phone', '081234567890')
        ->call('submitStep')
        ->assertHasNoErrors()
        ->assertRedirect(route('profiling.recommendation'));

    expect($user->refresh()->hasCompletedProfile())->toBeTrue();
});

test('a failed validation on submit keeps the typed values of the current step', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Wizard::class)
        ->set('step', 2)
        ->set('age', '61')
        ->call('submitStep')
        ->assertHasErrors(['age' => 'max'])
        ->assertSet('step', 2)
        ->assertSet('age', '61');
});

test('moving back and forth keeps the answers of every step', function () {
    $cluster = Cluster::factory()->create();
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Wizard::class)
        ->set('level', 's1')
        ->set('age', '21')
        ->set('gpa', '3.75')
        ->set('has_sktm', true)
        ->set('desired_level', 's2')
        ->set('desired_location', 'luar_negeri')
        ->set('cluster', (string) $cluster->id)
        ->set('phone', '081234567890')
        ->set('step', 4)
        ->call('goBack')
        ->assertSet('step', 3)
        ->assertSet('level', 's1')
        ->assertSet('age', '21')
        ->assertSet('gpa', '3.75')
        ->assertSet('desired_level', 's2')
        ->assertSet('desired_location', 'luar_negeri')
        ->assertSet('cluster', (string) $cluster->id);
});
