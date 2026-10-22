<?php

use App\Enums\AdminRole;
use App\Filament\Resources\Modules\ModuleResource;
use App\Filament\Resources\Modules\Pages\CreateModule;
use App\Filament\Resources\Modules\Pages\EditModule;
use App\Filament\Resources\Modules\Pages\ListModules;
use App\Filament\Resources\Modules\RelationManagers\PracticeFormsRelationManager;
use App\Filament\Resources\Modules\RelationManagers\QuickQuestionsRelationManager;
use App\Models\Fase;
use App\Models\Module;
use App\Models\PracticeForm;
use App\Models\QuickQuestion;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Create a super admin able to open the curriculum editor.
 */
function curriculumAdmin(): User
{
    $role = Role::findOrCreate(AdminRole::SuperAdmin->value, 'web');
    Permission::findOrCreate('ViewAny:Module', 'web');
    $role->syncPermissions(['ViewAny:Module']);

    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole($role);

    return $user;
}

test('the module editor shows the completeness checklist and the content managers', function () {
    $admin = curriculumAdmin();
    $module = Module::factory()->draft()->create();

    Livewire::actingAs($admin)
        ->test(EditModule::class, ['record' => $module->getRouteKey()])
        ->assertSee('Kelengkapan modul')
        ->assertSee('Materi Pahami tersedia')
        ->assertSee('Konteks untuk AI')
        ->assertSee('Quick Question')
        ->assertSee('Latihan praktik');
});

test('a quick question can be added from the module editor', function () {
    $admin = curriculumAdmin();
    $module = Module::factory()->draft()->create();

    Livewire::actingAs($admin)
        ->test(QuickQuestionsRelationManager::class, [
            'ownerRecord' => $module,
            'pageClass' => EditModule::class,
        ])
        ->callAction(TestAction::make('create')->table(), data: [
            'question' => 'Tujuan belajar sebaiknya terukur.',
            'answer' => true,
            'is_active' => true,
            'ordering' => 1,
        ])
        ->assertHasNoActionErrors();

    $question = QuickQuestion::query()->where('module_id', $module->id)->first();

    expect($question)->not->toBeNull()
        ->and($question->question)->toBe('Tujuan belajar sebaiknya terukur.')
        ->and($question->answer)->toBeTrue();
});

test('a practice exercise can be built from the module editor', function () {
    $admin = curriculumAdmin();
    $module = Module::factory()->draft()->create();

    Livewire::actingAs($admin)
        ->test(PracticeFormsRelationManager::class, [
            'ownerRecord' => $module,
            'pageClass' => EditModule::class,
        ])
        ->callAction(TestAction::make('create')->table(), data: [
            'title' => 'Latihan Motivasi',
            'description' => 'Tuliskan motivasi utamamu.',
            'ordering' => 1,
            'is_active' => true,
            'include_in_cv' => false,
            'fields' => [
                [
                    'name' => 'motivasi',
                    'label' => 'Motivasi utama',
                    'type' => 'textarea',
                    'required' => true,
                ],
            ],
        ])
        ->assertHasNoActionErrors();

    $form = PracticeForm::query()->where('module_id', $module->id)->first();

    expect($form)->not->toBeNull()
        ->and($form->fields)->toBeArray()
        ->and($form->fields[0]['name'])->toBe('motivasi');
});

test('the module form stores important points as a list the mentee view can render', function () {
    $admin = curriculumAdmin();
    $fase = Fase::factory()->create();

    Livewire::actingAs($admin)
        ->test(CreateModule::class)
        ->fillForm([
            'fase_id' => $fase->id,
            'title' => 'Modul Poin Penting',
            'modul_order' => 1,
            'category' => 'inti',
            'type' => 'reading',
            'insights' => [
                ['value' => 'Tujuan harus terukur.'],
                ['value' => 'Kaitkan dengan kontribusi.'],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $module = Module::query()->where('title', 'Modul Poin Penting')->firstOrFail();

    expect($module->insights)->toBe(['Tujuan harus terukur.', 'Kaitkan dengan kontribusi.']);
});

test('a module that stored points as plain text keeps them when edited', function () {
    $admin = curriculumAdmin();
    $module = Module::factory()->create();

    // The previous editor stored the points as one text value, one per line,
    // which the array cast turns into a JSON string.
    $module->update(['insights' => "Poin pertama\nPoin kedua"]);

    Livewire::actingAs($admin)
        ->test(EditModule::class, ['record' => $module->getRouteKey()])
        ->assertSchemaStateSet([
            'insights' => [
                ['value' => 'Poin pertama'],
                ['value' => 'Poin kedua'],
            ],
        ]);

    expect($module->refresh()->insights)->toBe(['Poin pertama', 'Poin kedua']);
});

test('the module list reports the content status and completeness of each module', function () {
    $admin = curriculumAdmin();
    $module = Module::factory()->draft()->create(['title' => 'Modul Daftar Kurikulum']);

    Livewire::actingAs($admin)
        ->test(ListModules::class)
        ->assertCanSeeTableRecords([$module])
        ->assertSee('Kelengkapan')
        ->assertSee('Status');
});

test('the module created from the panel starts as a draft', function () {
    $admin = curriculumAdmin();
    $fase = Fase::factory()->create();

    Livewire::actingAs($admin)
        ->test(CreateModule::class)
        ->fillForm([
            'fase_id' => $fase->id,
            'title' => 'Modul Baru Draf',
            'modul_order' => 1,
            'category' => 'inti',
            'type' => 'reading',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $module = Module::query()->where('title', 'Modul Baru Draf')->firstOrFail();

    expect($module->isDraft())->toBeTrue();
});

test('the preview page renders the mentee content without touching progress', function () {
    $admin = curriculumAdmin();

    $module = Module::factory()->draft()->create([
        'title' => 'Modul Pratinjau',
        'goal' => 'Mentee mampu menulis pembuka esai.',
    ]);

    PracticeForm::factory()->create([
        'module_id' => $module->id,
        'title' => 'Latihan Pembuka Esai',
        'fields' => [
            [
                'name' => 'pembuka',
                'label' => 'Paragraf pembuka',
                'type' => 'textarea',
                'required' => true,
                'placeholder' => null,
                'help_text' => 'Maksimal 150 kata.',
                'options' => [],
            ],
        ],
    ]);

    QuickQuestion::factory()->create([
        'module_id' => $module->id,
        'question' => 'Pembuka esai boleh bersifat umum.',
        'answer' => false,
        'explanation' => 'Pembuka harus spesifik.',
        'ordering' => 1,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->get(ModuleResource::getUrl('preview', ['record' => $module]))
        ->assertOk()
        ->assertSee('Modul Pratinjau')
        ->assertSee('Latihan Pembuka Esai')
        ->assertSee('Pembuka esai boleh bersifat umum.')
        ->assertSee('Paragraf pembuka');

    // Previewing must never start a checkpoint or store a practice answer.
    $this->assertDatabaseCount('checkpoints', 0);
    $this->assertDatabaseCount('user_practice_responses', 0);
});

test('the preview page is forbidden for an admin without module access', function () {
    $role = Role::findOrCreate(AdminRole::CoAdmin->value, 'web');
    Permission::findOrCreate('ViewAny:User', 'web');
    $role->syncPermissions(['ViewAny:User']);

    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole($role);

    $module = Module::factory()->create();

    $this->actingAs($admin)
        ->get(ModuleResource::getUrl('preview', ['record' => $module]))
        ->assertForbidden();
});
