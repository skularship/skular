<?php

use App\Models\Checkpoint;
use App\Models\Fase;
use App\Models\Module;
use App\Models\Scholarship;
use App\Models\ScholarshipApplication;
use App\Models\User;
use App\Models\UserPracticeResponse;

test('a scholarship selected by a mentee cannot be deleted', function () {
    $application = ScholarshipApplication::factory()->create();
    $scholarship = $application->scholarship;

    expect($scholarship->delete())->toBeFalse()
        ->and(Scholarship::query()->whereKey($scholarship->id)->exists())->toBeTrue()
        ->and(ScholarshipApplication::query()->whereKey($application->id)->exists())->toBeTrue();
});

test('a scholarship nobody selected can still be deleted', function () {
    $scholarship = Scholarship::factory()->create();

    expect($scholarship->delete())->toBeTrue()
        ->and(Scholarship::query()->whereKey($scholarship->id)->exists())->toBeFalse();
});

test('a module with mentee progress cannot be deleted, and neither can its fase', function () {
    $checkpoint = Checkpoint::factory()->create();
    $module = $checkpoint->module;
    $fase = $module->fase;

    expect($module->delete())->toBeFalse()
        ->and($fase->delete())->toBeFalse()
        ->and(Checkpoint::query()->whereKey($checkpoint->id)->exists())->toBeTrue();
});

test('a module with a practice answer cannot be deleted', function () {
    $response = UserPracticeResponse::factory()->create();

    expect($response->module->delete())->toBeFalse()
        ->and(UserPracticeResponse::query()->whereKey($response->id)->exists())->toBeTrue();
});

test('untouched modules and fases can still be deleted', function () {
    $fase = Fase::factory()->create();
    $module = Module::factory()->create(['fase_id' => $fase->id]);

    // Another mentee's progress elsewhere must not block this fase.
    Checkpoint::factory()->create(['user_id' => User::factory()]);

    expect($module->delete())->toBeTrue()
        ->and($fase->delete())->toBeTrue();
});
