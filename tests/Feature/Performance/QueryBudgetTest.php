<?php

use App\Contracts\LearningPathServiceInterface;
use App\Contracts\ProgressServiceInterface;
use App\Enums\ModuleCategory;
use App\Models\Fase;
use App\Models\Module;
use App\Models\PracticeForm;
use App\Models\Scholarship;
use App\Models\ScholarshipApplication;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Seed a mentee with one active scholarship, a second queued scholarship and a
 * realistic curriculum size.
 *
 * @return array{mentee: User, first_module: Module, scholarship_b: Scholarship}
 */
function performanceFixture(): array
{
    $mentee = User::factory()->profiled()->create();

    $fase_one = Fase::factory()->create(['fase_order' => 1]);
    $fase_two = Fase::factory()->create(['fase_order' => 2]);

    $scholarship_a = Scholarship::factory()->create([
        'closing_date' => now()->addDays(10)->toDateString(),
    ]);
    $scholarship_b = Scholarship::factory()->create([
        'closing_date' => now()->addDays(20)->toDateString(),
    ]);

    $first_module = null;

    foreach ([$fase_one, $fase_two] as $index => $fase) {
        for ($order = 1; $order <= 6; $order++) {
            $module = Module::factory()->create([
                'fase_id' => $fase->id,
                'modul_order' => $order,
                'category' => ModuleCategory::Inti,
            ]);

            $first_module ??= $module;
        }

        Module::factory()->template($scholarship_a)->create([
            'fase_id' => $fase->id,
            'modul_order' => 7,
        ]);

        Module::factory()->template($scholarship_b)->create([
            'fase_id' => $fase->id,
            'modul_order' => 7,
        ]);

        $index++;
    }

    PracticeForm::factory()->create(['module_id' => $first_module->id]);

    ScholarshipApplication::factory()->primary()->create([
        'user_id' => $mentee->id,
        'scholarship_id' => $scholarship_a->id,
    ]);

    ScholarshipApplication::factory()->create([
        'user_id' => $mentee->id,
        'scholarship_id' => $scholarship_b->id,
    ]);

    return [
        'mentee' => $mentee,
        'first_module' => $first_module,
        'scholarship_b' => $scholarship_b,
    ];
}

/**
 * Count the database queries executed while running the callback.
 *
 * @return array{count: int, time_ms: float}
 */
function measureQueries(callable $callback): array
{
    $queries = 0;
    $listener = function () use (&$queries): void {
        $queries++;
    };

    DB::listen($listener);

    $start = microtime(true);
    $callback();
    $time_ms = (microtime(true) - $start) * 1000;

    return ['count' => $queries, 'time_ms' => round($time_ms, 2)];
}

test('mentee hot pages stay within their query budget', function () {
    $data = performanceFixture();

    $this->actingAs($data['mentee']);

    $dashboard = measureQueries(fn () => $this->get(route('dashboard'))->assertOk());
    $module = measureQueries(fn () => $this->get(route('modules.show', $data['first_module']))->assertOk());
    $catalog = measureQueries(fn () => $this->get(route('scholarships.index'))->assertOk());
    $library = measureQueries(fn () => $this->get(route('library.index'))->assertOk());

    fwrite(STDERR, sprintf(
        "\nQUERY BUDGET dashboard=%d (%.2fms) module=%d (%.2fms) catalog=%d (%.2fms) library=%d (%.2fms)\n",
        $dashboard['count'],
        $dashboard['time_ms'],
        $module['count'],
        $module['time_ms'],
        $catalog['count'],
        $catalog['time_ms'],
        $library['count'],
        $library['time_ms'],
    ));

    expect($dashboard['count'])->toBeLessThanOrEqual(10)
        ->and($module['count'])->toBeLessThanOrEqual(25)
        ->and($catalog['count'])->toBeLessThanOrEqual(8)
        ->and($library['count'])->toBeLessThanOrEqual(6);
});

test('progress overview reads the curriculum a bounded number of times', function () {
    $data = performanceFixture();

    /** @var ProgressServiceInterface $progress */
    $progress = app(ProgressServiceInterface::class);

    $result = measureQueries(function () use ($progress, $data): void {
        for ($i = 0; $i < 5; $i++) {
            $progress->summary($data['mentee']);
            $progress->roadmap($data['mentee']);
        }
    });

    fwrite(STDERR, sprintf("\nPROGRESS 5x summary+roadmap: %d queries (%.2fms)\n", $result['count'], $result['time_ms']));

    expect($result['count'])->toBeLessThanOrEqual(38);
});

test('progress overview latency baseline is recorded', function () {
    $data = performanceFixture();

    /** @var ProgressServiceInterface $progress */
    $progress = app(ProgressServiceInterface::class);

    // Warm the content cache so the samples reflect steady-state reads.
    $progress->overview($data['mentee']);

    $samples = [];

    for ($i = 0; $i < 100; $i++) {
        $started_at = microtime(true);
        $progress->overview($data['mentee']);
        $samples[] = (microtime(true) - $started_at) * 1000;
    }

    sort($samples);

    $p50 = round($samples[49], 2);
    $p95 = round($samples[94], 2);

    fwrite(STDERR, sprintf("\nOVERVIEW LATENCY p50=%.2fms p95=%.2fms (100 reads, sqlite in-memory)\n", $p50, $p95));

    expect($samples)->toHaveCount(100)
        ->and($p95)->toBeGreaterThan(0);
});

test('cached curriculum is invalidated when content changes', function () {
    $data = performanceFixture();
    $path = app(LearningPathServiceInterface::class);

    // Warm the cache for the active scholarship path.
    expect($path->roadmapModules($data['mentee'])->pluck('id')->all())->not->toBeEmpty();

    $data['first_module']->update(['title' => 'Judul Setelah Kurikulum Diubah']);

    expect($path->roadmapModules($data['mentee'])->firstWhere('id', $data['first_module']->id)?->title)
        ->toBe('Judul Setelah Kurikulum Diubah');

    $new_module = Module::factory()->create([
        'fase_id' => $data['first_module']->fase_id,
        'modul_order' => 99,
        'category' => ModuleCategory::Inti,
    ]);

    expect($path->roadmapModules($data['mentee'])->pluck('id')->all())->toContain($new_module->id);
});

test('cached catalog locations are invalidated when a scholarship changes', function () {
    $mentee = User::factory()->profiled()->create();

    $scholarship = Scholarship::factory()->create(['location' => 'Kota Alpha']);

    $this->actingAs($mentee)->get(route('scholarships.index'))->assertOk()->assertSee('Kota Alpha');

    $scholarship->update(['location' => 'Kota Beta']);

    $this->actingAs($mentee)->get(route('scholarships.index'))->assertOk()->assertSee('Kota Beta');
});
