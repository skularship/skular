<?php

use App\Contracts\LearningPathServiceInterface;
use App\Contracts\ProgressServiceInterface;
use App\Enums\ApplicationStatus;
use App\Enums\CheckpointStatus;
use App\Enums\ModuleCategory;
use App\Models\Fase;
use App\Models\Module;
use App\Models\Scholarship;
use App\Models\ScholarshipApplication;
use App\Models\User;

beforeEach(function () {
    $this->mentee = User::factory()->profiled()->create();
    $this->fase = Fase::factory()->create(['fase_order' => 1]);
    $this->path = app(LearningPathServiceInterface::class);
});

test('core modules appear on every mentee roadmap', function () {
    $core = Module::factory()->create(['fase_id' => $this->fase->id, 'modul_order' => 1, 'category' => ModuleCategory::Inti]);

    $ids = $this->path->roadmapModules($this->mentee)->pluck('id')->all();

    expect($ids)->toContain($core->id);
});

test('a template module only appears for its selected scholarship', function () {
    $scholarship_a = Scholarship::factory()->create();
    $scholarship_b = Scholarship::factory()->create();

    $template_a = Module::factory()->template($scholarship_a)->create(['fase_id' => $this->fase->id, 'modul_order' => 2]);
    $template_b = Module::factory()->template($scholarship_b)->create(['fase_id' => $this->fase->id, 'modul_order' => 3]);

    ScholarshipApplication::factory()->create([
        'user_id' => $this->mentee->id,
        'scholarship_id' => $scholarship_a->id,
        'status' => ApplicationStatus::Selected,
    ]);

    $ids = app(LearningPathServiceInterface::class)->roadmapModules($this->mentee)->pluck('id')->all();

    expect($ids)->toContain($template_a->id)
        ->and($ids)->not->toContain($template_b->id);
});

test('library modules are excluded from the roadmap but exposed separately', function () {
    $library = Module::factory()->library()->create(['fase_id' => $this->fase->id, 'modul_order' => 1]);

    $roadmap_ids = $this->path->roadmapModules($this->mentee)->pluck('id')->all();
    $library_ids = $this->path->libraryModules()->pluck('id')->all();

    expect($roadmap_ids)->not->toContain($library->id)
        ->and($library_ids)->toContain($library->id);
});

test('a template module of an unselected scholarship cannot be opened', function () {
    $scholarship = Scholarship::factory()->create();

    $template = Module::factory()->template($scholarship)->create(['fase_id' => $this->fase->id, 'modul_order' => 1]);

    $this->actingAs($this->mentee)
        ->get(route('modules.show', $template))
        ->assertForbidden();
});

test('a library module is reachable without a scholarship', function () {
    $library = Module::factory()->library()->create(['fase_id' => $this->fase->id, 'modul_order' => 1]);

    $this->actingAs($this->mentee)
        ->get(route('modules.show', $library))
        ->assertOk();
});

test('progress is preserved when the active scholarship changes', function () {
    $core = Module::factory()->create(['fase_id' => $this->fase->id, 'modul_order' => 1, 'category' => ModuleCategory::Inti]);

    $progress = app(ProgressServiceInterface::class);
    $progress->completeModule($this->mentee, $core, 85);

    $scholarship = Scholarship::factory()->create();
    ScholarshipApplication::factory()->primary()->create([
        'user_id' => $this->mentee->id,
        'scholarship_id' => $scholarship->id,
    ]);
    Module::factory()->template($scholarship)->create(['fase_id' => $this->fase->id, 'modul_order' => 2]);

    app(LearningPathServiceInterface::class);

    $checkpoint = app(ProgressServiceInterface::class)->checkpointFor($this->mentee, $core);

    expect($checkpoint->status)->toBe(CheckpointStatus::Completed)
        ->and($checkpoint->score)->toBe(85);
});
