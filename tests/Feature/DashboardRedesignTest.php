<?php

use App\Enums\EducationLevel;
use App\Enums\LocationScope;
use App\Livewire\Dashboard;
use App\Models\Fase;
use App\Models\Module;
use App\Models\Scholarship;
use App\Models\ScholarshipApplication;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->mentee = User::factory()->profiled()->create([
        'level' => EducationLevel::S1,
        'desired_location' => LocationScope::DalamNegeri,
    ]);
});

test('the dashboard lists the scholarship queue ordered by deadline', function () {
    $later = Scholarship::factory()->create([
        'scholarship_name' => 'Beasiswa Lambat Dashboard',
        'closing_date' => now()->addDays(40)->toDateString(),
    ]);
    $sooner = Scholarship::factory()->create([
        'scholarship_name' => 'Beasiswa Cepat Dashboard',
        'closing_date' => now()->addDays(5)->toDateString(),
    ]);

    ScholarshipApplication::factory()->create([
        'user_id' => $this->mentee->id,
        'scholarship_id' => $later->id,
    ]);
    ScholarshipApplication::factory()->primary()->create([
        'user_id' => $this->mentee->id,
        'scholarship_id' => $sooner->id,
    ]);

    $component = Livewire::actingAs($this->mentee)->test(Dashboard::class);

    $component->assertSee('Antrean beasiswamu')
        ->assertSee('Beasiswa Cepat Dashboard');

    $order = $component->viewData('queue')->pluck('scholarship.scholarship_name')->all();

    expect($order)->toBe(['Beasiswa Cepat Dashboard', 'Beasiswa Lambat Dashboard'])
        ->and($component->viewData('active_application')->scholarship_id)->toBe($sooner->id);
});

test('the next best action asks for profiling when the profile is incomplete', function () {
    $fresh = User::factory()->create();

    Livewire::actingAs($fresh)
        ->test(Dashboard::class)
        ->assertSee('Lengkapi profiling dulu');
});

test('the next best action points to the current module', function () {
    $fase = Fase::factory()->create(['fase_order' => 1]);
    $module = Module::factory()->create([
        'fase_id' => $fase->id,
        'modul_order' => 1,
        'title' => 'Modul Langkah Berikutnya',
    ]);

    ScholarshipApplication::factory()->primary()->create([
        'user_id' => $this->mentee->id,
        'scholarship_id' => Scholarship::factory()->create()->id,
    ]);

    $component = Livewire::actingAs($this->mentee)->test(Dashboard::class);

    $action = $component->viewData('next_action');

    expect($action['title'])->toContain('Modul Langkah Berikutnya')
        ->and($action['url'])->toBe(route('modules.show', $module));
});

test('the dashboard reports the scholarship cap honestly', function () {
    collect(range(1, 5))->each(function (int $i): void {
        ScholarshipApplication::factory()->create([
            'user_id' => $this->mentee->id,
            'scholarship_id' => Scholarship::factory()->create()->id,
        ]);
    });

    Livewire::actingAs($this->mentee)
        ->test(Dashboard::class)
        ->assertSee('5/5');
});

test('the dashboard never reveals another mentee scholarship', function () {
    $other = User::factory()->profiled()->create();

    $scholarship = Scholarship::factory()->create(['scholarship_name' => 'Beasiswa Rahasia Dashboard']);

    ScholarshipApplication::factory()->primary()->create([
        'user_id' => $other->id,
        'scholarship_id' => $scholarship->id,
    ]);

    $this->actingAs($this->mentee)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Beasiswa Rahasia Dashboard');
});
