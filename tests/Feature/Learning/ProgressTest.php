<?php

use App\Contracts\ProgressServiceInterface;
use App\Enums\AiFeedbackStatus;
use App\Enums\CheckpointStatus;
use App\Enums\EducationLevel;
use App\Models\Fase;
use App\Models\Module;
use App\Models\PracticeForm;
use App\Models\Scholarship;
use App\Models\ScholarshipApplication;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

function curriculum(): array
{
    $fase = Fase::factory()->create(['fase_order' => 1]);

    return [
        $fase,
        Module::factory()->create(['fase_id' => $fase->id, 'modul_order' => 1, 'title' => 'Modul Pertama']),
        Module::factory()->create(['fase_id' => $fase->id, 'modul_order' => 2, 'title' => 'Modul Kedua']),
        Module::factory()->create(['fase_id' => $fase->id, 'modul_order' => 3, 'title' => 'Modul Ketiga']),
    ];
}

test('only the first module is available for a mentee who has not started', function () {
    [$fase, $first, $second, $third] = curriculum();
    $mentee = User::factory()->profiled()->create();

    $roadmap = app(ProgressServiceInterface::class)->roadmap($mentee);
    $modules = collect($roadmap[0]->modules);

    expect($roadmap)->toHaveCount(1)
        ->and($modules[0]->status)->toBe(CheckpointStatus::Available)
        ->and($modules[0]->isUnlocked())->toBeTrue()
        ->and($modules[1]->status)->toBe(CheckpointStatus::Locked)
        ->and($modules[2]->isLocked())->toBeTrue();
});

test('completing a module unlocks the next one', function () {
    [$fase, $first, $second] = curriculum();
    $mentee = User::factory()->profiled()->create();

    $progress = app(ProgressServiceInterface::class);
    $progress->completeModule($mentee, $first, 90);

    $modules = collect($progress->roadmap($mentee)[0]->modules);

    expect($modules[0]->status)->toBe(CheckpointStatus::Completed)
        ->and($modules[0]->score)->toBe(90)
        ->and($modules[1]->status)->toBe(CheckpointStatus::Available)
        ->and($modules[2]->status)->toBe(CheckpointStatus::Locked);
});

test('a locked module cannot be completed', function () {
    [$fase, $first, $second] = curriculum();
    $mentee = User::factory()->profiled()->create();

    $progress = app(ProgressServiceInterface::class);

    expect(fn () => $progress->completeModule($mentee, $second))
        ->toThrow(AuthorizationException::class);

    expect($progress->checkpointFor($mentee, $second)->status)->toBe(CheckpointStatus::Locked);
});

test('a module with practice forms cannot be completed before they are submitted', function () {
    [$fase, $first] = curriculum();
    $mentee = User::factory()->profiled()->create();

    $form = PracticeForm::factory()->create(['module_id' => $first->id]);
    $progress = app(ProgressServiceInterface::class);

    expect(fn () => $progress->completeModule($mentee, $first))
        ->toThrow(ValidationException::class);

    expect($progress->checkpointFor($mentee, $first)->status)->not->toBe(CheckpointStatus::Completed);

    $mentee->practiceResponses()->create([
        'module_id' => $first->id,
        'practice_form_id' => $form->id,
        'answers' => ['jawaban' => 'Jawaban lengkap saya.'],
        'ai_status' => AiFeedbackStatus::Completed,
        'ai_score' => 80,
        'attempts' => 1,
        'submitted_at' => now(),
    ]);

    expect($progress->completeModule($mentee, $first, 80)->status)->toBe(CheckpointStatus::Completed);
});

test('a module can be completed when its practice submission failed to get ai feedback', function () {
    [$fase, $first] = curriculum();
    $mentee = User::factory()->profiled()->create();

    $form = PracticeForm::factory()->create(['module_id' => $first->id]);
    $progress = app(ProgressServiceInterface::class);

    $mentee->practiceResponses()->create([
        'module_id' => $first->id,
        'practice_form_id' => $form->id,
        'answers' => ['jawaban' => 'Jawaban saya.'],
        'ai_status' => AiFeedbackStatus::Failed,
        'ai_score' => null,
        'attempts' => 1,
        'submitted_at' => now(),
    ]);

    expect($progress->completeModule($mentee, $first)->status)->toBe(CheckpointStatus::Completed);
});

test('a locked module cannot be started', function () {
    [$fase, $first, $second] = curriculum();
    $mentee = User::factory()->profiled()->create();

    $progress = app(ProgressServiceInterface::class);

    expect(fn () => $progress->startModule($mentee, $second))
        ->toThrow(AuthorizationException::class);

    expect($progress->checkpointFor($mentee, $second)->status)->toBe(CheckpointStatus::Locked);
});

test('starting an available module marks it in progress', function () {
    [$fase, $first] = curriculum();
    $mentee = User::factory()->profiled()->create();

    $progress = app(ProgressServiceInterface::class);
    $checkpoint = $progress->startModule($mentee, $first);

    expect($checkpoint->status)->toBe(CheckpointStatus::InProgress)
        ->and($checkpoint->started_at)->not->toBeNull();
});

test('the summary aggregates completed modules, percentage and average score', function () {
    [$fase, $first, $second, $third] = curriculum();
    $mentee = User::factory()->profiled()->create();

    $progress = app(ProgressServiceInterface::class);
    $progress->completeModule($mentee, $first, 80);
    $progress->completeModule($mentee, $second, 90);

    $summary = $progress->summary($mentee);

    expect($summary->completed_modules)->toBe(2)
        ->and($summary->total_modules)->toBe(3)
        ->and($summary->percentage)->toBe(67)
        ->and($summary->average_score)->toBe(85)
        ->and($summary->isFinished())->toBeFalse()
        ->and($summary->current_module_title)->toBe('Modul Ketiga');
});

test('the summary is finished once every module is completed', function () {
    [$fase, $first, $second, $third] = curriculum();
    $mentee = User::factory()->profiled()->create();

    $progress = app(ProgressServiceInterface::class);
    $progress->completeModule($mentee, $first, 80);
    $progress->completeModule($mentee, $second, 85);
    $progress->completeModule($mentee, $third, 90);

    $summary = $progress->summary($mentee);

    expect($summary->isFinished())->toBeTrue()
        ->and($summary->percentage)->toBe(100)
        ->and($summary->current_module_id)->toBeNull();
});

test('checkpoints are created once per module', function () {
    [$fase, $first] = curriculum();
    $mentee = User::factory()->profiled()->create();

    $progress = app(ProgressServiceInterface::class);
    $progress->ensureCheckpoints($mentee);
    $progress->ensureCheckpoints($mentee);

    expect($mentee->checkpoints()->count())->toBe(3);
});

test('the roadmap reports per fase progress', function () {
    [$fase, $first, $second, $third] = curriculum();
    $mentee = User::factory()->profiled()->create();

    $progress = app(ProgressServiceInterface::class);
    $progress->completeModule($mentee, $first);

    $fase_progress = $progress->roadmap($mentee)[0];

    expect($fase_progress->completed_modules)->toBe(1)
        ->and($fase_progress->total_modules)->toBe(3)
        ->and($fase_progress->percentage)->toBe(33)
        ->and($fase_progress->isCompleted())->toBeFalse();
});

test('the dashboard shows the primary scholarship and progress to the mentee', function () {
    [$fase, $first] = curriculum();
    $mentee = User::factory()->profiled()->create();

    $scholarship = Scholarship::factory()->forLevel(EducationLevel::S1)->create([
        'scholarship_name' => 'Beasiswa Dashboard Uji',
    ]);

    ScholarshipApplication::factory()->primary()->create([
        'user_id' => $mentee->id,
        'scholarship_id' => $scholarship->id,
    ]);

    $this->actingAs($mentee)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Beasiswa Dashboard Uji')
        ->assertSee('Roadmap belajar');
});
