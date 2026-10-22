<?php

use App\Contracts\LearningPathServiceInterface;
use App\Enums\ContentStatus;
use App\Enums\ModuleCategory;
use App\Models\Fase;
use App\Models\Module;
use App\Models\User;

beforeEach(function () {
    $this->mentee = User::factory()->profiled()->create();
    $this->fase = Fase::factory()->create(['fase_order' => 1]);
    $this->path = app(LearningPathServiceInterface::class);
});

test('a draft module is not part of the mentee roadmap', function () {
    $published = Module::factory()->create(['fase_id' => $this->fase->id, 'modul_order' => 1]);
    $draft = Module::factory()->draft()->create(['fase_id' => $this->fase->id, 'modul_order' => 2]);

    $ids = $this->path->roadmapModules($this->mentee)->pluck('id')->all();

    expect($ids)->toContain($published->id)
        ->and($ids)->not->toContain($draft->id);
});

test('a draft perpustakaan module is not listed in the library', function () {
    $draft = Module::factory()->library()->draft()->create(['fase_id' => $this->fase->id, 'modul_order' => 1]);

    expect($this->path->libraryModules()->pluck('id')->all())->not->toContain($draft->id);
});

test('a mentee cannot open a draft module even with the direct link', function () {
    $draft = Module::factory()->draft()->create(['fase_id' => $this->fase->id, 'modul_order' => 1]);

    $this->actingAs($this->mentee)
        ->get(route('modules.show', $draft))
        ->assertNotFound();
});

test('publishing a module makes it reachable for the mentee', function () {
    $module = Module::factory()->draft()->create([
        'fase_id' => $this->fase->id,
        'modul_order' => 1,
        'category' => ModuleCategory::Inti,
    ]);

    $module->update(['content_status' => ContentStatus::Published]);

    expect($this->path->roadmapModules($this->mentee)->pluck('id')->all())->toContain($module->id);

    $this->actingAs($this->mentee)
        ->get(route('modules.show', $module))
        ->assertOk();
});
