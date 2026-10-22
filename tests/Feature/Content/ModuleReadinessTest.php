<?php

use App\Contracts\ModuleReadinessServiceInterface;
use App\Data\ModuleReadinessCheckData;
use App\Enums\ModuleCategory;
use App\Enums\ModuleType;
use App\Models\Module;
use App\Models\PracticeForm;
use App\Models\QuickQuestion;
use App\Models\Scholarship;

/**
 * Resolve the module readiness service from the container.
 */
function readinessService(): ModuleReadinessServiceInterface
{
    return app(ModuleReadinessServiceInterface::class);
}

/**
 * Get one readiness check of a module, failing clearly when it is missing.
 */
function readinessCheck(Module $module, string $key): ModuleReadinessCheckData
{
    foreach (readinessService()->checks($module) as $check) {
        if ($check->key === $key) {
            return $check;
        }
    }

    throw new RuntimeException("The module has no readiness check named [{$key}].");
}

/**
 * Get the keys of the checks that block publication.
 *
 * @return array<int, string>
 */
function readinessBlockers(Module $module): array
{
    return array_map(fn (ModuleReadinessCheckData $check): string => $check->key, readinessService()->blockers($module));
}

test('a module with material, goal and introduction is ready to be published', function () {
    $module = Module::factory()->create();

    expect(readinessService()->isReady($module))->toBeTrue()
        ->and(readinessService()->progress($module))->toBe(100)
        ->and(readinessService()->blockers($module))->toBe([]);
});

test('a module without any material is blocked before publishing', function () {
    $module = Module::factory()->create([
        'summary_material' => null,
        'inti_material' => null,
        'insights' => null,
    ]);

    expect(readinessBlockers($module))->toContain('material')
        ->and(readinessService()->isReady($module))->toBeFalse();
});

test('a template module needs a scholarship before it can be published', function () {
    $scholarship = Scholarship::factory()->create();
    $linked = Module::factory()->template($scholarship)->create();
    $unlinked = Module::factory()->create([
        'category' => ModuleCategory::Template,
        'scholarship_id' => null,
    ]);

    expect(readinessBlockers($linked))->not->toContain('scholarship')
        ->and(readinessBlockers($unlinked))->toContain('scholarship');
});

test('a video module without a usable link is blocked before publishing', function () {
    $without_link = Module::factory()->create([
        'type' => ModuleType::Video,
        'link_video' => null,
    ]);

    $with_broken_link = Module::factory()->create([
        'type' => ModuleType::Video,
        'link_video' => 'youtube.com/bukan-url-valid',
    ]);

    $with_link = Module::factory()->withVideo()->create(['type' => ModuleType::Video]);

    expect(readinessBlockers($without_link))->toContain('video')
        ->and(readinessBlockers($with_broken_link))->toContain('video')
        ->and(readinessBlockers($with_link))->not->toContain('video');
});

test('quick questions must be complete once the module uses them', function () {
    $module = Module::factory()->create();

    QuickQuestion::factory()->create([
        'module_id' => $module->id,
        'question' => '',
        'answer' => true,
        'ordering' => 1,
        'is_active' => true,
    ]);

    expect(readinessBlockers($module->refresh()))->toContain('quick_questions');
});

test('a module without quick questions does not require them', function () {
    $module = Module::factory()->create();

    expect(readinessCheck($module, 'quick_questions')->passed)->toBeTrue()
        ->and(readinessCheck($module, 'quick_questions')->required)->toBeFalse();
});

test('an empty practice form is reported without blocking publication', function () {
    $module = Module::factory()->create();

    PracticeForm::factory()->create([
        'module_id' => $module->id,
        'fields' => [],
    ]);

    expect(readinessCheck($module->refresh(), 'practice_forms')->passed)->toBeFalse()
        ->and(readinessService()->isReady($module))->toBeTrue();
});

test('an invalid active brainstorming question blocks publication', function () {
    $module = Module::factory()->create([
        'brainstorming_questions' => [
            [
                'id' => 'q_broken',
                'question' => 'Pertanyaan tanpa pilihan',
                'type' => 'single_choice',
                'required' => true,
                'active' => true,
                'options' => [],
            ],
        ],
    ]);

    expect(readinessBlockers($module))->toContain('brainstorming')
        ->and(readinessService()->isReady($module))->toBeFalse();
});

test('a module without brainstorming questions is not blocked', function () {
    $module = Module::factory()->create(['brainstorming_questions' => null]);

    expect(readinessCheck($module, 'brainstorming')->passed)->toBeTrue()
        ->and(readinessCheck($module, 'brainstorming')->required)->toBeFalse()
        ->and(readinessService()->isReady($module))->toBeTrue();
});

test('optional checks lower the progress without blocking publication', function () {
    $module = Module::factory()->create([
        'goal' => null,
        'introduction' => null,
    ]);

    expect(readinessService()->progress($module))->toBeLessThan(100)
        ->and(readinessService()->isReady($module))->toBeTrue();
});
