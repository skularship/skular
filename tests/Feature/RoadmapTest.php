<?php

use App\Contracts\ProgressServiceInterface;
use App\Enums\ModuleCategory;
use App\Livewire\Dashboard;
use App\Livewire\Roadmap;
use App\Models\Fase;
use App\Models\Module;
use App\Models\Scholarship;
use App\Models\ScholarshipApplication;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

/**
 * Build four fases with two Inti modules each and a mentee with an active scholarship.
 *
 * @return array{mentee: User, fases: array<int, Fase>, modules: array<int, array<int, Module>>}
 */
function roadmapFixture(): array
{
    $mentee = User::factory()->profiled()->create();

    $scholarship = Scholarship::factory()->create(['scholarship_name' => 'Beasiswa Roadmap Uji']);

    ScholarshipApplication::factory()->primary()->create([
        'user_id' => $mentee->id,
        'scholarship_id' => $scholarship->id,
    ]);

    $fases = [];
    $modules = [];

    foreach (['Fase Fondasi', 'Fase Esai', 'Fase Wawancara', 'Fase Final'] as $index => $title) {
        $fase = Fase::factory()->create([
            'title' => $title,
            'subtitle' => 'Subjudul '.$title,
            'fase_order' => $index + 1,
        ]);

        $fases[] = $fase;

        foreach ([1, 2] as $order) {
            $modules[$index][] = Module::factory()->create([
                'fase_id' => $fase->id,
                'modul_order' => $order,
                'category' => ModuleCategory::Inti,
                'title' => "Modul {$title} {$order}",
            ]);
        }
    }

    return ['mentee' => $mentee, 'fases' => $fases, 'modules' => $modules];
}

/**
 * Complete the given modules in order through the real progress service.
 *
 * @param  array<int, Module>  $modules
 */
function roadmapComplete(User $mentee, array $modules): void
{
    $progress = app(ProgressServiceInterface::class);

    foreach ($modules as $module) {
        $progress->completeModule($mentee, $module);
    }
}

test('guests are redirected from the roadmap to the login page', function () {
    $this->get(route('roadmap.show'))->assertRedirect(route('login'));
});

test('the roadmap shows the active fase modules and never the module titles of later fases', function () {
    $data = roadmapFixture();

    $response = $this->actingAs($data['mentee'])->get(route('roadmap.show'))->assertOk();

    $response->assertSee('Roadmap Belajar')
        ->assertSee('Modul Fase Fondasi 1')
        ->assertSee('Modul Fase Fondasi 2')
        // The next fase appears by name only.
        ->assertSee('Fase Esai')
        ->assertDontSee('Modul Fase Esai 1')
        // Fases further ahead stay behind the clouds: no names, no modules.
        ->assertDontSee('Fase Wawancara')
        ->assertDontSee('Fase Final')
        ->assertDontSee('Modul Fase Wawancara 1')
        ->assertDontSee('Modul Fase Final 1')
        ->assertSee('Lihat lebih lanjut');
});

test('lihat lebih lanjut reveals only the names of locked fases', function () {
    $data = roadmapFixture();

    $component = Livewire::actingAs($data['mentee'])
        ->test(Roadmap::class)
        ->assertDontSee('Fase Wawancara')
        ->call('showLockedFases')
        ->assertSee('Fase Wawancara')
        ->assertSee('Subjudul Fase Final')
        ->assertDontSee('Modul Fase Wawancara 1')
        ->assertDontSee('Modul Fase Final 2')
        ->assertDontSee('Modul Fase Esai 1');

    // The Livewire snapshot must not carry module titles either.
    expect(json_encode($component->snapshot))
        ->toContain('show_locked_fases')
        ->not->toContain('Modul Fase Wawancara');
});

test('finishing a fase moves the focus to the next one', function () {
    $data = roadmapFixture();

    roadmapComplete($data['mentee'], $data['modules'][0]);

    $this->actingAs($data['mentee'])->get(route('roadmap.show'))
        ->assertOk()
        ->assertSee('Fase selesai')
        ->assertSee('Modul Fase Fondasi 1')
        ->assertSee('Modul Fase Esai 1')
        ->assertSee('Fase Wawancara')
        ->assertDontSee('Modul Fase Wawancara 1')
        ->assertDontSee('Fase Final');
});

test('a finished path shows every fase and the finished state', function () {
    $data = roadmapFixture();

    roadmapComplete($data['mentee'], array_merge(...$data['modules']));

    $this->actingAs($data['mentee'])->get(route('roadmap.show'))
        ->assertOk()
        ->assertSee('Semua fase di jalur ini sudah selesai')
        ->assertSee('Modul Fase Final 2')
        ->assertDontSee('Lihat lebih lanjut');
});

test('a mentee without a scholarship sees the inti roadmap and a prompt to choose one', function () {
    $mentee = User::factory()->profiled()->create();
    $fase = Fase::factory()->create(['title' => 'Fase Mandiri']);
    Module::factory()->create(['fase_id' => $fase->id, 'category' => ModuleCategory::Inti, 'title' => 'Modul Mandiri']);

    $this->actingAs($mentee)->get(route('roadmap.show'))
        ->assertOk()
        ->assertSee('Belum ada beasiswa aktif')
        ->assertSee('Modul Mandiri');
});

test('an empty curriculum shows the roadmap empty state', function () {
    $mentee = User::factory()->profiled()->create();

    $this->actingAs($mentee)->get(route('roadmap.show'))
        ->assertOk()
        ->assertSee('Roadmap belum tersedia');
});

test('a queued second scholarship is mentioned without showing its modules', function () {
    $data = roadmapFixture();
    $queued = Scholarship::factory()->create(['scholarship_name' => 'Beasiswa Antre Uji']);
    Module::factory()->template($queued)->create([
        'fase_id' => $data['fases'][0]->id,
        'modul_order' => 3,
        'title' => 'Template Antre Rahasia',
    ]);
    ScholarshipApplication::factory()->create(['user_id' => $data['mentee']->id, 'scholarship_id' => $queued->id]);

    $this->actingAs($data['mentee'])->get(route('roadmap.show'))
        ->assertOk()
        ->assertSee('+1 beasiswa lain di antreanmu')
        ->assertDontSee('Template Antre Rahasia');
});

test('the dashboard links to the roadmap without listing every module', function () {
    $data = roadmapFixture();

    Livewire::actingAs($data['mentee'])
        ->test(Dashboard::class)
        ->assertSee('Roadmap belajar')
        ->assertSee('Fase Fondasi')
        ->assertSee(route('roadmap.show'))
        ->assertDontSee('Modul Fase Esai 1')
        ->assertDontSee('Fase Wawancara');
});

test('focusing the roadmap never changes module unlock states', function () {
    $data = roadmapFixture();
    $progress = app(ProgressServiceInterface::class);

    $overview = $progress->overview($data['mentee']);
    $focus = $progress->focusRoadmap($overview->roadmap);

    expect($focus->active?->modules)->toEqual($overview->roadmap[0]->modules)
        ->and($focus->upcoming?->title)->toBe('Fase Esai')
        ->and(array_map(fn ($outline) => $outline->title, $focus->locked))->toBe(['Fase Wawancara', 'Fase Final']);
});

test('the roadmap page stays within its query budget', function () {
    $data = roadmapFixture();
    $this->actingAs($data['mentee']);

    // Warm the content cache like a real second visit, then count queries.
    $this->get(route('roadmap.show'))->assertOk();

    $queries = 0;
    DB::listen(function () use (&$queries): void {
        $queries++;
    });

    $this->get(route('roadmap.show'))->assertOk();

    expect($queries)->toBeLessThanOrEqual(10);
});
