<?php

use App\Contracts\LearningPathServiceInterface;
use App\Contracts\ProgressServiceInterface;
use App\Contracts\ScholarshipPathServiceInterface;
use App\Enums\CheckpointStatus;
use App\Enums\ModuleCategory;
use App\Livewire\Dashboard;
use App\Models\Fase;
use App\Models\Module;
use App\Models\Scholarship;
use App\Models\ScholarshipApplication;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

/**
 * Build two scholarships in one fase with shared Inti and distinct templates.
 *
 * @return array{
 *     mentee: User,
 *     fase: Fase,
 *     first: Module,
 *     second: Module,
 *     scholarship_a: Scholarship,
 *     scholarship_b: Scholarship,
 *     application_a: ScholarshipApplication,
 *     application_b: ScholarshipApplication,
 *     template_a: Module,
 *     template_b: Module
 * }
 */
function sharedCurriculum(): array
{
    $mentee = User::factory()->profiled()->create();
    $fase = Fase::factory()->create(['fase_order' => 1]);

    $first = Module::factory()->create([
        'fase_id' => $fase->id,
        'modul_order' => 1,
        'category' => ModuleCategory::Inti,
        'title' => 'Inti Pertama',
    ]);

    $second = Module::factory()->create([
        'fase_id' => $fase->id,
        'modul_order' => 2,
        'category' => ModuleCategory::Inti,
        'title' => 'Inti Kedua',
    ]);

    $scholarship_a = Scholarship::factory()->create([
        'scholarship_name' => 'Beasiswa Alpha',
        'closing_date' => now()->addDays(5)->toDateString(),
    ]);

    $scholarship_b = Scholarship::factory()->create([
        'scholarship_name' => 'Beasiswa Beta',
        'closing_date' => now()->addDays(20)->toDateString(),
    ]);

    $template_a = Module::factory()->template($scholarship_a)->create([
        'fase_id' => $fase->id,
        'modul_order' => 3,
        'title' => 'Template Alpha',
    ]);

    $template_b = Module::factory()->template($scholarship_b)->create([
        'fase_id' => $fase->id,
        'modul_order' => 3,
        'title' => 'Template Beta',
    ]);

    $application_a = ScholarshipApplication::factory()->primary()->create([
        'user_id' => $mentee->id,
        'scholarship_id' => $scholarship_a->id,
    ]);

    $application_b = ScholarshipApplication::factory()->create([
        'user_id' => $mentee->id,
        'scholarship_id' => $scholarship_b->id,
    ]);

    return compact(
        'mentee',
        'fase',
        'first',
        'second',
        'scholarship_a',
        'scholarship_b',
        'application_a',
        'application_b',
        'template_a',
        'template_b',
    );
}

/**
 * Complete every given module in curriculum order.
 *
 * @param  array<int, Module>  $modules
 */
function completeModules(User $mentee, array $modules): void
{
    $progress = app(ProgressServiceInterface::class);

    foreach ($modules as $module) {
        $progress->completeModule($mentee, $module);
    }
}

test('the active curriculum is the shared inti plus the active scholarship template', function () {
    $data = sharedCurriculum();

    $ids = app(LearningPathServiceInterface::class)->roadmapModules($data['mentee'])->pluck('id')->all();

    expect($ids)->toBe([
        $data['first']->id,
        $data['second']->id,
        $data['template_a']->id,
    ])->not->toContain($data['template_b']->id);
});

test('an inti module completed once is shared by the next scholarship', function () {
    $data = sharedCurriculum();

    completeModules($data['mentee'], [$data['first'], $data['second'], $data['template_a']]);

    $progress = app(ProgressServiceInterface::class);

    expect($progress->isPathCompleted($data['mentee']))->toBeTrue();

    $next = app(ScholarshipPathServiceInterface::class)
        ->advance($data['mentee'], $data['application_a']->id);

    expect($next?->id)->toBe($data['application_b']->id)
        ->and($data['application_a']->refresh()->hasCompletedLearning())->toBeTrue()
        ->and($data['application_b']->refresh()->is_primary)->toBeTrue();

    $summary = $progress->summary($data['mentee']);

    expect($summary->total_modules)->toBe(3)
        ->and($summary->completed_modules)->toBe(2)
        ->and($summary->core_completed_modules)->toBe(2)
        ->and($summary->core_total_modules)->toBe(2)
        ->and($summary->template_completed_modules)->toBe(0)
        ->and($summary->template_total_modules)->toBe(1)
        ->and($summary->percentage)->toBe(67)
        ->and($summary->isFinished())->toBeFalse();

    // The shared module keeps exactly one checkpoint row, no copy per path.
    expect($data['mentee']->checkpoints()->where('module_id', $data['first']->id)->count())->toBe(1)
        ->and($data['mentee']->checkpoints()->where('module_id', $data['second']->id)->count())->toBe(1);

    $roadmap = collect($progress->roadmap($data['mentee']))->flatMap(fn ($fase) => collect($fase->modules));

    expect($roadmap->firstWhere('id', $data['template_b']->id)->status)->toBe(CheckpointStatus::Available)
        ->and($roadmap->firstWhere('id', $data['first']->id)->status)->toBe(CheckpointStatus::Completed)
        ->and($roadmap->firstWhere('id', $data['first']->id)->isShared())->toBeTrue();
});

test('template progress never carries over to another scholarship', function () {
    $data = sharedCurriculum();

    completeModules($data['mentee'], [$data['first'], $data['second'], $data['template_a']]);

    app(ScholarshipPathServiceInterface::class)->advance($data['mentee'], $data['application_a']->id);

    $progress = app(ProgressServiceInterface::class);

    // The finished template keeps its completed checkpoint, but it is no longer
    // part of the active curriculum, so the module page cannot reopen it.
    expect($data['mentee']->checkpoints()->where('module_id', $data['template_a']->id)->first()?->status)
        ->toBe(CheckpointStatus::Completed)
        ->and($progress->checkpointFor($data['mentee'], $data['template_a']))->toBeNull()
        ->and($progress->checkpointFor($data['mentee'], $data['template_b'])->status)->toBe(CheckpointStatus::Available)
        ->and($progress->summary($data['mentee'])->template_completed_modules)->toBe(0);
});

test('a library module never counts towards scholarship completion', function () {
    $data = sharedCurriculum();

    $library = Module::factory()->library()->create([
        'fase_id' => $data['fase']->id,
        'modul_order' => 4,
        'title' => 'TOEFL Tambahan',
    ]);

    completeModules($data['mentee'], [$library]);

    $progress = app(ProgressServiceInterface::class);
    $summary = $progress->summary($data['mentee']);

    expect($progress->checkpointFor($data['mentee'], $library)->status)->toBe(CheckpointStatus::Completed)
        ->and($summary->total_modules)->toBe(3)
        ->and($summary->completed_modules)->toBe(0)
        ->and($progress->isPathCompleted($data['mentee']))->toBeFalse();
});

test('a transition is rejected while the active path is unfinished', function () {
    $data = sharedCurriculum();

    expect(fn () => app(ScholarshipPathServiceInterface::class)
        ->advance($data['mentee'], $data['application_a']->id))
        ->toThrow(ValidationException::class);

    expect($data['application_a']->refresh()->hasCompletedLearning())->toBeFalse()
        ->and($data['application_a']->refresh()->is_primary)->toBeTrue();
});

test('repeating the same transition is idempotent', function () {
    $data = sharedCurriculum();

    completeModules($data['mentee'], [$data['first'], $data['second'], $data['template_a']]);

    $paths = app(ScholarshipPathServiceInterface::class);

    $next = $paths->advance($data['mentee'], $data['application_a']->id);
    $again = $paths->advance($data['mentee'], $data['application_a']->id);

    expect($next?->id)->toBe($data['application_b']->id)
        ->and($again?->id)->toBe($data['application_b']->id)
        ->and($data['mentee']->scholarshipApplications()->where('is_primary', true)->count())->toBe(1)
        ->and($data['mentee']->scholarshipApplications()->whereNotNull('learning_completed_at')->count())->toBe(1);
});

test('finishing the last path leaves no active scholarship but keeps the history', function () {
    $data = sharedCurriculum();

    // Drop the second scholarship so the first one is the whole queue.
    $data['application_b']->delete();

    completeModules($data['mentee'], [$data['first'], $data['second'], $data['template_a']]);

    $paths = app(ScholarshipPathServiceInterface::class);
    $next = $paths->advance($data['mentee'], $data['application_a']->id);

    expect($next)->toBeNull()
        ->and($data['mentee']->checkpoints()->where('module_id', $data['template_a']->id)->first()?->status)
        ->toBe(CheckpointStatus::Completed)
        ->and($paths->history($data['mentee'])->pluck('id')->all())->toBe([$data['application_a']->id]);

    Livewire::actingAs($data['mentee'])
        ->test(Dashboard::class)
        ->assertSee('Semua jalur beasiswa selesai')
        ->assertSee('Riwayat jalur belajar')
        ->assertSee('Beasiswa Alpha');
});

test('a new inti module published later reopens the completed path', function () {
    $data = sharedCurriculum();

    completeModules($data['mentee'], [$data['first'], $data['second'], $data['template_a']]);

    $progress = app(ProgressServiceInterface::class);

    expect($progress->isPathCompleted($data['mentee']))->toBeTrue();

    Module::factory()->create([
        'fase_id' => $data['fase']->id,
        'modul_order' => 9,
        'category' => ModuleCategory::Inti,
        'title' => 'Inti Baru',
    ]);

    expect($progress->isPathCompleted($data['mentee']))->toBeFalse();

    expect(fn () => app(ScholarshipPathServiceInterface::class)
        ->advance($data['mentee'], $data['application_a']->id))
        ->toThrow(ValidationException::class);
});

test('a template of a selected but inactive scholarship cannot be opened', function () {
    $data = sharedCurriculum();

    $this->actingAs($data['mentee'])
        ->get(route('modules.show', $data['template_b']))
        ->assertForbidden();

    $progress = app(ProgressServiceInterface::class);

    // The active scholarship template exists on the path (locked until the
    // preceding Inti modules are done), while the other template does not.
    expect($progress->checkpointFor($data['mentee'], $data['template_a']))->not->toBeNull()
        ->and($progress->checkpointFor($data['mentee'], $data['template_b']))->toBeNull();
});

test('rendering the dashboard never writes progress rows', function () {
    $data = sharedCurriculum();

    $this->actingAs($data['mentee'])
        ->get(route('dashboard'))
        ->assertOk();

    expect($data['mentee']->checkpoints()->count())->toBe(0);
});

test('the dashboard only offers the next scholarship once the path is complete', function () {
    $data = sharedCurriculum();

    Livewire::actingAs($data['mentee'])
        ->test(Dashboard::class)
        ->assertDontSee('advance-path', false);

    completeModules($data['mentee'], [$data['first'], $data['second'], $data['template_a']]);

    Livewire::actingAs($data['mentee'])
        ->test(Dashboard::class)
        ->assertSee('advance-path', false)
        ->assertSee('Beasiswa Beta')
        ->call('advanceToNextScholarship')
        ->assertHasNoErrors();

    expect($data['application_b']->refresh()->is_primary)->toBeTrue()
        ->and($data['application_a']->refresh()->hasCompletedLearning())->toBeTrue();
});

test('the checkpoint endpoint verifies ownership of the active curriculum', function () {
    $data = sharedCurriculum();
    $other = User::factory()->profiled()->create();

    $this->actingAs($other)
        ->get(route('modules.show', $data['first']))
        ->assertOk();

    expect(fn () => app(ProgressServiceInterface::class)->startModule($other, $data['template_a']))
        ->toThrow(AuthorizationException::class);
});
