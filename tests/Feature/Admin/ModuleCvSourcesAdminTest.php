<?php

use App\Contracts\ModuleReadinessServiceInterface;
use App\Enums\AdminRole;
use App\Filament\Resources\Modules\Pages\EditModule;
use App\Filament\Resources\Modules\RelationManagers\PracticeFormsRelationManager;
use App\Models\Module;
use App\Models\PracticeForm;
use App\Models\User;
use App\Services\ContentCache;
use Database\Seeders\RoleSeeder;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->curriculum_admin = User::factory()->create(['email_verified_at' => now()]);
    $this->curriculum_admin->assignRole(AdminRole::StaffDivisi->value);

    $this->source_module = Module::factory()->create(['title' => 'Personal Branding']);
    $this->source = PracticeForm::factory()->includeInCv(1)->create([
        'module_id' => $this->source_module->id,
        'title' => 'Pengalaman Organisasi',
    ]);

    $this->module = Module::factory()->create(['title' => 'Esai Khusus Beasiswa']);
});

test('an admin links a cv source to a module and the content cache is bumped', function () {
    $version = ContentCache::version();

    Livewire::actingAs($this->curriculum_admin)
        ->test(EditModule::class, ['record' => $this->module->getRouteKey()])
        ->fillForm(['cvSources' => [$this->source->id]])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($this->module->cvSources()->pluck('practice_forms.id')->all())->toBe([$this->source->id])
        ->and(ContentCache::version())->toBeGreaterThan($version);
});

test('a form that is not flagged for the cv is refused as a source', function () {
    $not_cv = PracticeForm::factory()->create([
        'module_id' => $this->source_module->id,
        'title' => 'Latihan Wawancara',
    ]);

    Livewire::actingAs($this->curriculum_admin)
        ->test(EditModule::class, ['record' => $this->module->getRouteKey()])
        ->fillForm(['cvSources' => [$not_cv->id]])
        ->call('save')
        ->assertHasFormErrors(['cvSources']);

    expect($this->module->cvSources()->count())->toBe(0);
});

test('a form of the module being edited is refused as a source', function () {
    $own_form = PracticeForm::factory()->includeInCv(2)->create([
        'module_id' => $this->module->id,
        'title' => 'Latihan Milik Modul Ini',
    ]);

    Livewire::actingAs($this->curriculum_admin)
        ->test(EditModule::class, ['record' => $this->module->getRouteKey()])
        ->fillForm(['cvSources' => [$own_form->id]])
        ->call('save')
        ->assertHasFormErrors(['cvSources']);

    expect($this->module->cvSources()->count())->toBe(0);
});

test('the cv source picker is hidden for try out theory modules', function () {
    $theory = Module::factory()->library()->create(['is_tryout_theory' => true]);

    Livewire::actingAs($this->curriculum_admin)
        ->test(EditModule::class, ['record' => $theory->getRouteKey()])
        ->assertFormFieldHidden('cvSources');

    Livewire::actingAs($this->curriculum_admin)
        ->test(EditModule::class, ['record' => $this->module->getRouteKey()])
        ->assertFormFieldVisible('cvSources');
});

test('the cv button label is stored from the practice form editor', function () {
    Livewire::actingAs($this->curriculum_admin)
        ->test(PracticeFormsRelationManager::class, [
            'ownerRecord' => $this->source_module,
            'pageClass' => EditModule::class,
        ])
        ->callAction(TestAction::make('create')->table(), data: [
            'title' => 'Prestasi',
            'ordering' => 2,
            'is_active' => true,
            'include_in_cv' => true,
            'cv_ordering' => 2,
            'cv_button_label' => 'prestasiku',
            'fields' => [
                ['name' => 'prestasi', 'label' => 'Nama prestasi', 'type' => 'text', 'required' => true],
            ],
        ])
        ->assertHasNoActionErrors();

    expect(PracticeForm::query()->where('title', 'Prestasi')->sole()->cv_button_label)->toBe('prestasiku');
});

test('a hidden cv source is a non-blocking readiness warning', function () {
    $this->module->cvSources()->attach($this->source->id);
    $readiness = app(ModuleReadinessServiceInterface::class);

    $check = collect($readiness->checks($this->module->fresh()))->firstWhere('key', 'cv_sources');
    expect($check->passed)->toBeTrue();

    $this->source_module->update(['content_status' => 'draft']);

    $check = collect($readiness->checks($this->module->fresh()))->firstWhere('key', 'cv_sources');

    expect($check->passed)->toBeFalse()
        ->and($check->required)->toBeFalse()
        ->and(collect($readiness->blockers($this->module->fresh()))->pluck('key'))->not->toContain('cv_sources');
});
