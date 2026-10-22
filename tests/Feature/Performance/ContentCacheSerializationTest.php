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
use App\Services\ContentCache;
use Illuminate\Cache\Repository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Seed a small curriculum and a mentee whose scholarship is in focus.
 *
 * @return array{mentee: User, scholarship: Scholarship, fase: Fase, module: Module}
 */
function serializingCacheFixture(): array
{
    $mentee = User::factory()->profiled()->create();
    $fase = Fase::factory()->create(['fase_order' => 1]);
    $scholarship = Scholarship::factory()->create([
        'closing_date' => now()->addDays(10)->toDateString(),
    ]);

    $module = Module::factory()->create([
        'fase_id' => $fase->id,
        'modul_order' => 1,
        'category' => ModuleCategory::Inti,
    ]);

    Module::factory()->template($scholarship)->create([
        'fase_id' => $fase->id,
        'modul_order' => 2,
    ]);

    PracticeForm::factory()->create(['module_id' => $module->id]);

    ScholarshipApplication::factory()->primary()->create([
        'user_id' => $mentee->id,
        'scholarship_id' => $scholarship->id,
    ]);

    return [
        'mentee' => $mentee,
        'scholarship' => $scholarship,
        'fase' => $fase,
        'module' => $module,
    ];
}

/**
 * Point the application at a store that serializes values like production does.
 */
function useSerializingCacheStore(): void
{
    config(['cache.default' => 'database']);

    Cache::store('database')->clear();
}

test('cached curriculum is rebuilt as models from a serializing store', function () {
    useSerializingCacheStore();

    $data = serializingCacheFixture();
    $path = app(LearningPathServiceInterface::class);

    $first = $path->curriculumModules($data['scholarship']);

    // Only plain rows may be cached: a serialized model or collection comes
    // back as an incomplete class from the production cache store.
    expect(Cache::store('database')->get(ContentCache::key('curriculum:'.$data['scholarship']->id)))
        ->toBeArray();

    $second = $path->curriculumModules($data['scholarship']);

    expect($second)->toBeInstanceOf(Collection::class)
        ->and($second->pluck('id')->all())->toBe($first->pluck('id')->all())
        ->and($second->first())->toBeInstanceOf(Module::class)
        ->and($second->firstWhere('id', $data['module']->id)?->category)->toBe(ModuleCategory::Inti)
        ->and($second->firstWhere('id', $data['module']->id)?->practice_forms_count)->toBe(1);
});

test('cached fases are rebuilt as models from a serializing store', function () {
    useSerializingCacheStore();

    $data = serializingCacheFixture();
    $progress = app(ProgressServiceInterface::class);

    $progress->overview($data['mentee']);

    expect(Cache::store('database')->get(ContentCache::key('fases')))->toBeArray();

    $overview = $progress->overview($data['mentee']);

    expect($overview->roadmap)->toHaveCount(1)
        ->and($overview->roadmap[0]->id)->toBe($data['fase']->id)
        ->and($overview->roadmap[0]->total_modules)->toBe(2)
        ->and($overview->roadmap[0]->modules[0]->has_practice_form)->toBeTrue();
});

test('a legacy serialized curriculum entry is evicted instead of breaking the page', function () {
    useSerializingCacheStore();

    $data = serializingCacheFixture();
    $store = Cache::store('database');
    $key = ContentCache::key('curriculum:'.$data['scholarship']->id);

    // Reproduce an entry written by the old code: a serialized collection.
    $store->put($key, collect([$data['module']]), 3600);
    $store->put(
        Repository::FLEXIBLE_CREATED_KEY_PREFIX.$key,
        now()->getTimestamp(),
        3600,
    );

    expect($store->get($key))->toBeInstanceOf(__PHP_Incomplete_Class::class);

    $modules = app(LearningPathServiceInterface::class)->curriculumModules($data['scholarship']);

    expect($modules)->toBeInstanceOf(Collection::class)
        ->and($modules->first())->toBeInstanceOf(Module::class)
        ->and($modules->pluck('id')->all())->toContain($data['module']->id)
        ->and($modules->pluck('id')->all())->toHaveCount(2)
        ->and($store->get($key))->toBeArray();
});
