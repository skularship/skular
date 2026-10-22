<?php

use App\Enums\AdminRole;
use App\Filament\Resources\Modules\Pages\CreateModule;
use App\Filament\Resources\Modules\Pages\EditModule;
use App\Models\Fase;
use App\Models\Module;
use App\Models\User;
use App\Services\BrainstormingConfigService;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Create an admin able to edit the curriculum.
 */
function brainstormingAdmin(): User
{
    $role = Role::findOrCreate(AdminRole::SuperAdmin->value, 'web');
    Permission::findOrCreate('ViewAny:Module', 'web');
    $role->syncPermissions(['ViewAny:Module']);

    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole($role);

    return $user;
}

test('an admin can add brainstorming questions and options from the module editor', function () {
    $admin = brainstormingAdmin();
    $fase = Fase::factory()->create();

    Livewire::actingAs($admin)
        ->test(CreateModule::class)
        ->fillForm([
            'fase_id' => $fase->id,
            'title' => 'Modul dengan Pemancing Ide',
            'modul_order' => 1,
            'category' => 'inti',
            'type' => 'reading',
            'brainstorming_questions' => [
                [
                    'question' => 'Apa peran utamamu?',
                    'type' => 'single_choice',
                    'required' => true,
                    'active' => true,
                    'hint' => 'Pilih yang paling sering kamu jalankan.',
                    'options' => [
                        ['label' => 'Ketua', 'active' => true],
                        ['label' => 'Anggota', 'active' => true],
                    ],
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $module = Module::query()->where('title', 'Modul dengan Pemancing Ide')->firstOrFail();
    $config = $module->brainstorming_questions;

    expect($config)->toBeArray()->toHaveCount(1)
        ->and($config[0]['question'])->toBe('Apa peran utamamu?')
        ->and($config[0]['type'])->toBe('single_choice')
        ->and($config[0]['required'])->toBeTrue()
        ->and($config[0]['active'])->toBeTrue()
        ->and($config[0]['id'])->toStartWith('q_')
        ->and($config[0]['options'])->toHaveCount(2)
        ->and($config[0]['options'][0]['label'])->toBe('Ketua')
        ->and($config[0]['options'][0]['id'])->toStartWith('o_')
        ->and($config[0]['options'][0]['value'])->toBe($config[0]['options'][0]['id']);
});

test('question and option identifiers stay stable when the module is saved again', function () {
    $admin = brainstormingAdmin();

    $config = app(BrainstormingConfigService::class)->normaliseForStorage([
        [
            'question' => 'Apa peran utamamu?',
            'type' => 'single_choice',
            'required' => true,
            'active' => true,
            'options' => [
                ['label' => 'Ketua', 'active' => true],
                ['label' => 'Anggota', 'active' => true],
            ],
        ],
    ]);

    $module = Module::factory()->draft()->create([
        'brainstorming_questions' => $config,
    ]);

    $question_id = $module->brainstorming_questions[0]['id'];
    $option_ids = array_column($module->brainstorming_questions[0]['options'], 'id');

    Livewire::actingAs($admin)
        ->test(EditModule::class, ['record' => $module->getRouteKey()])
        ->call('save')
        ->assertHasNoFormErrors();

    $saved = $module->refresh()->brainstorming_questions;

    expect($saved[0]['id'])->toBe($question_id)
        ->and(array_column($saved[0]['options'], 'id'))->toBe($option_ids);
});

test('the module editor rejects a choice question without valid options', function () {
    $admin = brainstormingAdmin();
    $module = Module::factory()->draft()->create();

    Livewire::actingAs($admin)
        ->test(EditModule::class, ['record' => $module->getRouteKey()])
        ->fillForm([
            'brainstorming_questions' => [
                [
                    'question' => 'Pertanyaan tanpa pilihan',
                    'type' => 'single_choice',
                    'required' => true,
                    'active' => true,
                    'options' => [],
                ],
            ],
        ])
        ->call('save')
        ->assertHasFormErrors(['brainstorming_questions']);
});

test('an inactive question is saved as a draft without blocking publication', function () {
    $admin = brainstormingAdmin();
    $module = Module::factory()->draft()->create();

    Livewire::actingAs($admin)
        ->test(EditModule::class, ['record' => $module->getRouteKey()])
        ->fillForm([
            'brainstorming_questions' => [
                [
                    'question' => 'Pertanyaan yang belum siap',
                    'type' => 'single_choice',
                    'required' => false,
                    'active' => false,
                    'options' => [],
                ],
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $saved = $module->refresh()->brainstorming_questions;

    expect($saved)->toHaveCount(1)
        ->and($saved[0]['active'])->toBeFalse()
        ->and(app(BrainstormingConfigService::class)->validationMessages($saved))->toBe([]);
});
