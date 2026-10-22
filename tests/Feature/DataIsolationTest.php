<?php

use App\Contracts\ProgressServiceInterface;
use App\Contracts\ScholarshipApplicationServiceInterface;
use App\Enums\AiFeedbackStatus;
use App\Enums\ApplicationResult;
use App\Enums\EducationLevel;
use App\Livewire\Learning\ModulePlayer;
use App\Livewire\Scholarships\Tracker;
use App\Models\Fase;
use App\Models\Module;
use App\Models\PracticeForm;
use App\Models\Scholarship;
use App\Models\ScholarshipApplication;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Livewire\Livewire;

/**
 * Build one fase with a single ordered module.
 *
 * @return array{0: Fase, 1: Module}
 */
function isolatedCurriculum(): array
{
    $fase = Fase::factory()->create(['fase_order' => 1]);

    return [
        $fase,
        Module::factory()->create([
            'fase_id' => $fase->id,
            'modul_order' => 1,
            'title' => 'Modul Isolasi',
        ]),
    ];
}

test('the scholarship application service rejects another mentee application', function () {
    $mentee = User::factory()->profiled()->create();
    $other = User::factory()->profiled()->create();

    $application = ScholarshipApplication::factory()->create([
        'user_id' => $other->id,
        'scholarship_id' => Scholarship::factory()->create()->id,
    ]);

    $service = app(ScholarshipApplicationServiceInterface::class);

    expect(fn () => $service->confirmSubmission($mentee, $application, null, 'bukan milikku'))
        ->toThrow(AuthorizationException::class);

    expect(fn () => $service->reportResult($mentee, $application, ApplicationResult::Accepted))
        ->toThrow(AuthorizationException::class);

    expect(fn () => $service->setPrimary($mentee, $application))
        ->toThrow(AuthorizationException::class);

    expect($application->refresh()->status->value)->toBe('selected')
        ->and($application->result)->toBeNull();
});

test('the tracker cannot open another mentee application', function () {
    $mentee = User::factory()->profiled()->create();
    $other = User::factory()->profiled()->create();

    $application = ScholarshipApplication::factory()->create([
        'user_id' => $other->id,
        'scholarship_id' => Scholarship::factory()->create()->id,
    ]);

    // The owner-scoped lookup fails, so the confirmation panel never opens.
    Livewire::actingAs($mentee)
        ->test(Tracker::class)
        ->call('openConfirm', $application->id)
        ->assertSet('confirming_id', null);

    expect($application->refresh()->status->value)->toBe('selected');
});

test('the dashboard never reveals another mentee scholarship', function () {
    $mentee = User::factory()->profiled()->create();
    $other = User::factory()->profiled()->create();

    $scholarship = Scholarship::factory()->forLevel(EducationLevel::S1)->create([
        'scholarship_name' => 'Beasiswa Rahasia Mentee Lain',
    ]);

    ScholarshipApplication::factory()->primary()->create([
        'user_id' => $other->id,
        'scholarship_id' => $scholarship->id,
    ]);

    $this->actingAs($mentee)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Beasiswa Rahasia Mentee Lain');
});

test('learning progress and practice answers are scoped per mentee', function () {
    [, $module] = isolatedCurriculum();

    $mentee = User::factory()->profiled()->create();
    $other = User::factory()->profiled()->create();

    $form = PracticeForm::factory()->create(['module_id' => $module->id]);

    $other->practiceResponses()->create([
        'module_id' => $module->id,
        'practice_form_id' => $form->id,
        'answers' => ['jawaban' => 'Jawaban rahasia mentee lain.'],
        'ai_status' => AiFeedbackStatus::Completed,
        'ai_score' => 90,
        'attempts' => 1,
        'submitted_at' => now(),
    ]);

    $progress = app(ProgressServiceInterface::class);
    $progress->completeModule($other, $module, 90);

    // The other mentee progressed, but this mentee's own learning state is untouched.
    expect($progress->summary($mentee)->completed_modules)->toBe(0)
        ->and($progress->checkpointFor($mentee, $module)->isCompleted())->toBeFalse();

    Livewire::actingAs($mentee)
        ->test(ModulePlayer::class, ['module' => $module])
        ->assertSet('answers', []);
});
