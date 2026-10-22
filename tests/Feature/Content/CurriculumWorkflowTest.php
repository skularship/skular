<?php

use App\Contracts\LearningPathServiceInterface;
use App\Enums\AdminRole;
use App\Filament\Resources\Modules\Pages\CreateModule;
use App\Filament\Resources\Modules\Pages\EditModule;
use App\Filament\Resources\Modules\RelationManagers\PracticeFormsRelationManager;
use App\Filament\Resources\Modules\RelationManagers\QuickQuestionsRelationManager;
use App\Models\Fase;
use App\Models\Module;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Create a super admin that can author curriculum content.
 */
function curriculumWorkflowAdmin(): User
{
    $role = Role::findOrCreate(AdminRole::SuperAdmin->value, 'web');
    Permission::findOrCreate('ViewAny:Module', 'web');
    $role->syncPermissions(['ViewAny:Module']);

    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole($role);

    return $user;
}

test('a module authored in the panel reaches the mentee learning experience', function () {
    $admin = curriculumWorkflowAdmin();
    $fase = Fase::factory()->create();

    // 1. The curriculum team creates the module. It starts as a draft.
    Livewire::actingAs($admin)
        ->test(CreateModule::class)
        ->fillForm([
            'fase_id' => $fase->id,
            'title' => 'Modul Alur Kurikulum',
            'modul_order' => 1,
            'category' => 'inti',
            'type' => 'reading',
            'duration' => 20,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $module = Module::query()->where('title', 'Modul Alur Kurikulum')->firstOrFail();

    expect($module->isDraft())->toBeTrue();

    // 2. The material and the introduction are written in the editor.
    Livewire::actingAs($admin)
        ->test(EditModule::class, ['record' => $module->getRouteKey()])
        ->fillForm([
            'goal' => 'Mentee memahami alur kurikulum.',
            'introduction' => 'Pengantar sebelum latihan.',
            'summary_material' => 'Ringkasan materi alur kurikulum.',
            'inti_material' => 'Inti materi alur kurikulum.',
            'insights' => [
                ['value' => 'Poin uji pertama'],
                ['value' => 'Poin uji kedua'],
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    // 3. A quick question and a practice exercise are added from the same page.
    Livewire::actingAs($admin)
        ->test(QuickQuestionsRelationManager::class, [
            'ownerRecord' => $module->refresh(),
            'pageClass' => EditModule::class,
        ])
        ->callAction(TestAction::make('create')->table(), data: [
            'question' => 'Pernyataan uji alur kurikulum.',
            'answer' => true,
            'is_active' => true,
            'ordering' => 1,
        ])
        ->assertHasNoActionErrors();

    Livewire::actingAs($admin)
        ->test(PracticeFormsRelationManager::class, [
            'ownerRecord' => $module->refresh(),
            'pageClass' => EditModule::class,
        ])
        ->callAction(TestAction::make('create')->table(), data: [
            'title' => 'Latihan alur kurikulum',
            'description' => 'Tuliskan pemahamanmu.',
            'ordering' => 1,
            'is_active' => true,
            'include_in_cv' => false,
            'fields' => [
                [
                    'name' => 'pemahaman',
                    'label' => 'Pemahamanmu',
                    'type' => 'textarea',
                    'required' => true,
                ],
            ],
        ])
        ->assertHasNoActionErrors();

    // 4. Publishing is allowed now that the checklist passes.
    Livewire::actingAs($admin)
        ->test(EditModule::class, ['record' => $module->getRouteKey()])
        ->callAction('publish')
        ->assertNotified();

    expect($module->refresh()->isPublished())->toBeTrue();

    // 5. The mentee sees the module and its content on the learning path.
    $mentee = User::factory()->profiled()->create();

    expect(app(LearningPathServiceInterface::class)->roadmapModules($mentee)->pluck('id')->all())
        ->toContain($module->id);

    // The opening step shows the goal written in the editor.
    $this->actingAs($mentee)
        ->get(route('modules.show', $module))
        ->assertOk()
        ->assertSee('Modul Alur Kurikulum')
        ->assertSee('Mentee memahami alur kurikulum.');

    // The Pahami step shows the material and the important points.
    $this->actingAs($mentee)
        ->get(route('modules.show', ['module' => $module, 'section' => 'pahami']))
        ->assertOk()
        ->assertSee('Ringkasan materi alur kurikulum.')
        ->assertSee('Poin uji pertama');
});
