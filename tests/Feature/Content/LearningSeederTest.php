<?php

use App\Contracts\ModuleReadinessServiceInterface;
use App\Enums\ModuleCategory;
use App\Enums\ModuleType;
use App\Models\Module;
use App\Models\Scholarship;
use Database\Seeders\ClusterSeeder;
use Database\Seeders\LearningSeeder;
use Database\Seeders\ScholarshipSeeder;

/**
 * Seed the curriculum together with the scholarships it can be tailored to.
 */
function seedLearningData(): void
{
    test()->seed([ClusterSeeder::class, ScholarshipSeeder::class, LearningSeeder::class]);
}

test('the learning seeder provides content for every module type', function () {
    seedLearningData();

    foreach (ModuleType::cases() as $type) {
        expect(Module::query()->where('type', $type->value)->exists())
            ->toBeTrue("Expected seeded content for the [{$type->value}] module type.");
    }
});

test('the learning seeder tailors template modules to every scholarship', function () {
    seedLearningData();

    $readiness = app(ModuleReadinessServiceInterface::class);

    expect(Scholarship::query()->count())->toBeGreaterThan(0);

    foreach (Scholarship::query()->get() as $scholarship) {
        $modules = $scholarship->templateModules()->get();

        expect($modules)->not->toBeEmpty()
            ->and($modules->every(fn (Module $module): bool => $module->category === ModuleCategory::Template))->toBeTrue()
            ->and($modules->every(fn (Module $module): bool => $readiness->isReady($module)))->toBeTrue();
    }
});

test('re-seeding the learning data does not duplicate modules', function () {
    seedLearningData();

    $module_count = Module::query()->count();

    $this->seed(LearningSeeder::class);

    expect(Module::query()->count())->toBe($module_count);
});
