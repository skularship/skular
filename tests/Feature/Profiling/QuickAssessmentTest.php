<?php

use App\Enums\ReadinessCategory;
use App\Livewire\Profiling\QuickAssessment;
use App\Livewire\Profiling\Wizard;
use App\Models\User;
use Livewire\Livewire;

/**
 * Fill every quick assessment answer except the ones under test.
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
        ->assertSee('Pertanyaan 1 dari 5');
});

test('a guest can run the readiness check without logging in', function () {
    Livewire::test(QuickAssessment::class)
        ->assertSet('step', 1)
        ->assertSet('submitted', false)
        ->assertSee('Status kamu sekarang?')
        ->set(readyQuickAnswers())
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('submitted', true)
        ->assertSee('Saatnya Memperkuat Modal Beasiswamu');

    $this->assertGuest();
    expect(User::query()->count())->toBe(0);
});

test('a question cannot be skipped without an answer', function () {
    Livewire::test(QuickAssessment::class)
        ->call('next')
        ->assertHasErrors(['currentStatus' => 'required'])
        ->assertSet('step', 1);
});

test('an answer outside the allowed options is rejected', function () {
    Livewire::test(QuickAssessment::class)
        ->set('currentStatus', 'president')
        ->call('advance')
        ->assertHasErrors(['currentStatus']);
});

test('a submission without a preparation asset is rejected', function () {
    $answers = readyQuickAnswers();
    unset($answers['assets']);

    Livewire::test(QuickAssessment::class)
        ->set($answers)
        ->call('submit')
        ->assertHasErrors(['assets' => 'required'])
        ->assertSet('submitted', false);
});

test('an unknown preparation asset is rejected', function () {
    Livewire::test(QuickAssessment::class)
        ->set('assets', ['bukan_modal'])
        ->set(array_diff_key(readyQuickAnswers(), ['assets' => null]))
        ->call('submit')
        ->assertHasErrors(['assets.0'])
        ->assertSet('submitted', false);
});

test('"Belum Ada" cannot be combined with another preparation asset', function () {
    Livewire::test(QuickAssessment::class)
        ->set('assets', ['none', 'essay_draft'])
        ->set(array_diff_key(readyQuickAnswers(), ['assets' => null]))
        ->call('submit')
        ->assertHasErrors(['assets'])
        ->assertSet('submitted', false);
});

test('choosing "Belum Ada" clears every other preparation asset', function () {
    Livewire::test(QuickAssessment::class)
        ->call('toggleAsset', 'essay_draft')
        ->call('toggleAsset', 'organization')
        ->assertSet('assets', ['essay_draft', 'organization'])
        ->call('toggleAsset', 'none')
        ->assertSet('assets', ['none']);
});

test('choosing a real preparation asset clears "Belum Ada"', function () {
    Livewire::test(QuickAssessment::class)
        ->call('toggleAsset', 'none')
        ->assertSet('assets', ['none'])
        ->call('toggleAsset', 'language_score')
        ->assertSet('assets', ['language_score']);
});

test('tapping the active card releases it again', function () {
    Livewire::test(QuickAssessment::class)
        ->call('selectChoice', 'currentStatus', 'sma')
        ->assertSet('currentStatus', 'sma')
        ->call('selectChoice', 'currentStatus', 'sma')
        ->assertSet('currentStatus', null)
        ->call('toggleAsset', 'none')
        ->call('toggleAsset', 'none')
        ->assertSet('assets', []);
});

test('navigating back and forth keeps every answer', function () {
    Livewire::test(QuickAssessment::class)
        ->set('currentStatus', 'sma')
        ->call('next')
        ->assertSet('step', 2)
        ->set('target', 'beasiswa_s1')
        ->call('next')
        ->call('goBack')
        ->assertSet('step', 2)
        ->assertSet('currentStatus', 'sma')
        ->assertSet('target', 'beasiswa_s1');
});

test('answering a question swaps the cards for the next one', function () {
    Livewire::test(QuickAssessment::class)
        ->assertSee('Status kamu sekarang?')
        ->call('selectChoice', 'currentStatus', 'sma')
        ->call('next')
        ->assertSet('step', 2)
        ->assertSee('Apa yang mau kamu kejar?')
        ->assertDontSee('Status kamu sekarang?')
        ->call('selectChoice', 'target', 'beasiswa_s1')
        ->call('next')
        ->assertSet('step', 3)
        ->assertSee('Kapan target daftar?')
        ->assertDontSee('Apa yang mau kamu kejar?')
        ->call('selectChoice', 'registerTimeline', '3_6_months')
        ->call('next')
        ->assertSet('step', 4)
        ->assertSee('Apa yang sudah kamu punya?')
        ->assertDontSee('Kapan target daftar?')
        ->call('toggleAsset', 'essay_draft')
        ->call('next')
        ->assertSet('step', 5)
        ->assertSee('Berapa waktu yang bisa kamu alokasikan setiap minggu?')
        ->assertDontSee('Apa yang sudah kamu punya?');
});

test('stepping back shows the previous question again', function () {
    Livewire::test(QuickAssessment::class)
        ->call('selectChoice', 'currentStatus', 'sma')
        ->call('next')
        ->assertSee('Apa yang mau kamu kejar?')
        ->call('goBack')
        ->assertSet('step', 1)
        ->assertSee('Status kamu sekarang?')
        ->assertDontSee('Apa yang mau kamu kejar?');
});

test('the result replaces the form and points at the registration route', function () {
    Livewire::test(QuickAssessment::class)
        ->set(readyQuickAnswers())
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSee('Tiga langkah berikutnya')
        ->assertSee('Bangun Kesiapanmu Bersama Skular')
        ->assertSee(route('register'))
        ->assertDontSee('Status kamu sekarang?');
});

test('restarting clears the answers and shows the form again', function () {
    Livewire::test(QuickAssessment::class)
        ->set(readyQuickAnswers())
        ->call('submit')
        ->assertSet('submitted', true)
        ->call('restart')
        ->assertSet('submitted', false)
        ->assertSet('step', 1)
        ->assertSet('currentStatus', null)
        ->assertSet('target', null)
        ->assertSet('registerTimeline', null)
        ->assertSet('assets', [])
        ->assertSet('weeklyPrepTime', null)
        ->assertHasNoErrors()
        ->assertSee('Status kamu sekarang?');
});

test('the displayed category always matches the computed score', function () {
    Livewire::test(QuickAssessment::class)
        ->set([
            'currentStatus' => 'sma',
            'target' => 's2_luar_negeri',
            'registerTimeline' => 'under_3_months',
            'assets' => ['none'],
            'weeklyPrepTime' => 'under_2_hours',
        ])
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSee('Persiapanmu Sudah Dimulai!')
        ->assertSee('Perlu Persiapan Lebih Lanjut');

    expect(ReadinessCategory::forScore(18))->toBe(ReadinessCategory::NeedsPreparation);
});

test('the quick assessment leaves the 8 step profiling wizard untouched', function () {
    $mentee = User::factory()->create();

    Livewire::actingAs($mentee)
        ->test(QuickAssessment::class)
        ->set(readyQuickAnswers())
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('submitted', true);

    expect($mentee->refresh()->profile_completed_at)->toBeNull()
        ->and(session('profiling_level'))->toBeNull();

    Livewire::actingAs($mentee)
        ->test(Wizard::class)
        ->assertSet('step', 1)
        ->assertSet('level', null)
        ->assertHasNoErrors();
});

test('the login-free check works while a mentee is signed in without touching their profile', function () {
    $mentee = User::factory()->profiled()->create();

    $original = $mentee->only(['level', 'age', 'gpa', 'has_sktm', 'desired_level', 'desired_location', 'cluster_id', 'phone']);

    Livewire::actingAs($mentee)
        ->test(QuickAssessment::class)
        ->set([
            'currentStatus' => 'worker',
            'target' => 'lomba',
            'registerTimeline' => 'over_12_months',
            'assets' => ['achievement'],
            'weeklyPrepTime' => 'over_5_hours',
        ])
        ->call('submit')
        ->assertHasNoErrors();

    expect($mentee->refresh()->only(array_keys($original)))->toBe($original);
});

test('the landing page keeps its registration and login calls to action', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee(route('register'), false)
        ->assertSee(route('login'), false);
});
