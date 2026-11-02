<?php

use App\Enums\ReadinessCategory;
use App\Livewire\Profiling\QuickAssessment;
use App\Livewire\Profiling\Wizard;
use App\Models\User;
use Livewire\Livewire;

/**
 * A complete, valid Profiling Kilat batch as the browser sends it.
 *
 * @return array<string, mixed>
 */
function readyQuickAnswers(): array
{
    return [
        'currentStatus' => 's1',
        'target' => 's2_dalam_negeri',
        'registerTimeline' => '3_6_months',
        'assets' => ['essay_draft', 'organization'],
        'weeklyPrepTime' => '2_5_hours',
    ];
}

test('the landing page renders the profiling kilat section', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Seberapa Siap Kamu Mengejar Beasiswa?')
        ->assertSee('Pertanyaan')
        ->assertSee('dari 5')
        ->assertSee('Lihat Hasil Kesiapan');
});

test('every single choice question is one radio group and nothing syncs per pick', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('name="answers.currentStatus"', false)
        ->assertSee('x-model="answers.currentStatus"', false)
        ->assertSee('x-model="answers.target"', false)
        ->assertSee('x-model="answers.registerTimeline"', false)
        ->assertSee('x-model="answers.weeklyPrepTime"', false)
        ->assertSee('type="radio"', false)
        ->assertSee('type="checkbox"', false)
        ->assertDontSee('$wire.set', false)
        ->assertDontSee('wire:model="currentStatus"', false);
});

test('answering and navigating are served by frontend state only', function () {
    expect(method_exists(QuickAssessment::class, 'selectChoice'))->toBeFalse()
        ->and(method_exists(QuickAssessment::class, 'toggleAsset'))->toBeFalse()
        ->and(method_exists(QuickAssessment::class, 'next'))->toBeFalse()
        ->and(method_exists(QuickAssessment::class, 'goBack'))->toBeFalse()
        ->and(method_exists(QuickAssessment::class, 'advance'))->toBeFalse();
});

test('a guest can run the readiness check without logging in', function () {
    Livewire::test(QuickAssessment::class)
        ->assertSet('submitted', false)
        ->assertSee('Status kamu sekarang?')
        ->call('submit', readyQuickAnswers())
        ->assertReturned(fn (array $result): bool => $result['ok'] === true)
        ->assertHasNoErrors()
        ->assertSet('submitted', true)
        ->assertSee('Saatnya Memperkuat Modal Beasiswamu');

    $this->assertGuest();
    expect(User::query()->count())->toBe(0);
});

test('an empty batch is rejected and points at the first question', function () {
    Livewire::test(QuickAssessment::class)
        ->call('submit', [])
        ->assertReturned(fn (array $result): bool => $result['ok'] === false && $result['step'] === 1)
        ->assertHasErrors(['currentStatus'])
        ->assertSet('submitted', false);
});

test('a batch missing a later answer is rejected and points at that question', function () {
    $answers = readyQuickAnswers();
    unset($answers['weeklyPrepTime']);

    Livewire::test(QuickAssessment::class)
        ->call('submit', $answers)
        ->assertReturned(fn (array $result): bool => $result['ok'] === false && $result['step'] === 5)
        ->assertHasErrors(['weeklyPrepTime'])
        ->assertSet('submitted', false);
});

test('an answer outside the allowed options is rejected', function () {
    Livewire::test(QuickAssessment::class)
        ->call('submit', [...readyQuickAnswers(), 'currentStatus' => 'president'])
        ->assertHasErrors(['currentStatus'])
        ->assertSet('submitted', false);
});

test('a submission without a preparation asset is rejected', function () {
    $answers = readyQuickAnswers();
    unset($answers['assets']);

    Livewire::test(QuickAssessment::class)
        ->call('submit', $answers)
        ->assertHasErrors(['assets'])
        ->assertSet('submitted', false);
});

test('an unknown preparation asset is rejected', function () {
    Livewire::test(QuickAssessment::class)
        ->call('submit', [...readyQuickAnswers(), 'assets' => ['bukan_modal']])
        ->assertHasErrors(['assets.0'])
        ->assertSet('submitted', false);
});

test('"Belum Ada" cannot be combined with another preparation asset', function () {
    Livewire::test(QuickAssessment::class)
        ->call('submit', [...readyQuickAnswers(), 'assets' => ['none', 'essay_draft']])
        ->assertHasErrors(['assets'])
        ->assertSet('submitted', false);
});

test('"Belum Ada" alone is accepted', function () {
    Livewire::test(QuickAssessment::class)
        ->call('submit', [...readyQuickAnswers(), 'assets' => ['none']])
        ->assertHasNoErrors()
        ->assertSet('submitted', true);
});

test('duplicate answers in the batch are normalised before scoring', function () {
    Livewire::test(QuickAssessment::class)
        ->call('submit', [...readyQuickAnswers(), 'assets' => ['essay_draft', 'essay_draft']])
        ->assertHasNoErrors()
        ->assertSet('answers.assets', ['essay_draft']);
});

test('the displayed category always matches the computed score', function () {
    Livewire::test(QuickAssessment::class)
        ->call('submit', [
            'currentStatus' => 'sma',
            'target' => 's2_luar_negeri',
            'registerTimeline' => 'under_3_months',
            'assets' => ['none'],
            'weeklyPrepTime' => 'under_2_hours',
        ])
        ->assertHasNoErrors()
        ->assertSee('Persiapanmu Sudah Dimulai!')
        ->assertSee('Perlu Persiapan Lebih Lanjut');

    expect(ReadinessCategory::forScore(18))->toBe(ReadinessCategory::NeedsPreparation);
});

test('the result replaces the form and points at the registration route', function () {
    Livewire::test(QuickAssessment::class)
        ->call('submit', readyQuickAnswers())
        ->assertHasNoErrors()
        ->assertSee('Tiga langkah berikutnya')
        ->assertSee('Bangun Kesiapanmu Bersama Skular')
        ->assertSee(route('register'))
        ->assertDontSee('Status kamu sekarang?');
});

test('restarting clears the answers and shows the form again', function () {
    Livewire::test(QuickAssessment::class)
        ->call('submit', readyQuickAnswers())
        ->assertSet('submitted', true)
        ->call('restart')
        ->assertSet('submitted', false)
        ->assertSet('answers', [])
        ->assertHasNoErrors()
        ->assertSee('Status kamu sekarang?');
});

test('submitting the same batch twice stays idempotent', function () {
    $component = Livewire::test(QuickAssessment::class);

    $component->call('submit', readyQuickAnswers())->assertHasNoErrors();
    $component->call('submit', readyQuickAnswers())
        ->assertReturned(fn (array $result): bool => $result['ok'] === true)
        ->assertHasNoErrors()
        ->assertSet('submitted', true);

    expect(User::query()->count())->toBe(0);
});

test('the quick assessment leaves the 8 step profiling wizard untouched', function () {
    $mentee = User::factory()->create();

    Livewire::actingAs($mentee)
        ->test(QuickAssessment::class)
        ->call('submit', readyQuickAnswers())
        ->assertHasNoErrors()
        ->assertSet('submitted', true);

    expect($mentee->refresh()->profile_completed_at)->toBeNull()
        ->and(session('profiling_level'))->toBeNull();

    Livewire::actingAs($mentee)
        ->test(Wizard::class)
        ->assertSet('step', 1)
        ->assertSet('answers.level', null)
        ->assertHasNoErrors();
});

test('the login-free check works while a mentee is signed in without touching their profile', function () {
    $mentee = User::factory()->profiled()->create();

    $original = $mentee->only(['level', 'age', 'gpa', 'has_sktm', 'desired_level', 'desired_location', 'cluster_id', 'phone']);

    Livewire::actingAs($mentee)
        ->test(QuickAssessment::class)
        ->call('submit', [
            'currentStatus' => 'worker',
            'target' => 'lomba',
            'registerTimeline' => 'over_12_months',
            'assets' => ['achievement'],
            'weeklyPrepTime' => 'over_5_hours',
        ])
        ->assertHasNoErrors();

    expect($mentee->refresh()->only(array_keys($original)))->toBe($original);
});
