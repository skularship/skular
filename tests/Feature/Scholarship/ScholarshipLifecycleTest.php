<?php

use App\Contracts\ScholarshipApplicationServiceInterface;
use App\Enums\AiFeedbackStatus;
use App\Enums\ApplicationResult;
use App\Enums\ApplicationStatus;
use App\Enums\EducationLevel;
use App\Enums\LocationScope;
use App\Models\Fase;
use App\Models\Module;
use App\Models\PracticeForm;
use App\Models\Scholarship;
use App\Models\ScholarshipApplication;
use App\Models\User;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->mentee = User::factory()->profiled()->create([
        'level' => EducationLevel::S1,
        'desired_location' => LocationScope::DalamNegeri,
    ]);

    $this->service = app(ScholarshipApplicationServiceInterface::class);
});

function lifecycleScholarship(string $name, int $days): Scholarship
{
    return Scholarship::factory()->forLevel(EducationLevel::S1)->withoutRequirements()->create([
        'scholarship_name' => $name,
        'scope' => LocationScope::DalamNegeri,
        'closing_date' => now()->addDays($days)->toDateString(),
    ]);
}

test('a result cannot be reported before the submission is confirmed', function () {
    $application = ScholarshipApplication::factory()->create([
        'user_id' => $this->mentee->id,
        'scholarship_id' => lifecycleScholarship('Beasiswa A', 10)->id,
    ]);

    expect(fn () => $this->service->reportResult($this->mentee, $application, ApplicationResult::Accepted))
        ->toThrow(ValidationException::class);

    expect($application->refresh()->result)->toBeNull();
});

test('reporting an accepted result advances the queue to the next deadline', function () {
    $first = lifecycleScholarship('Beasiswa Terdekat', 5);
    $second = lifecycleScholarship('Beasiswa Berikutnya', 20);

    $applications = collect([$second, $first])->map(fn (Scholarship $scholarship): ScholarshipApplication => ScholarshipApplication::factory()->submitted()->create([
        'user_id' => $this->mentee->id,
        'scholarship_id' => $scholarship->id,
    ]));

    $this->service->select($this->mentee, [], $first->id);

    $nearest = $this->mentee->scholarshipApplications()->where('scholarship_id', $first->id)->first();

    $this->service->reportResult($this->mentee, $nearest, ApplicationResult::Accepted);

    $this->mentee->refresh();

    expect($nearest->refresh()->result)->toBe(ApplicationResult::Accepted)
        ->and($this->service->activeApplication($this->mentee)?->scholarship_id)->toBe($second->id)
        ->and($this->service->activeApplication($this->mentee)?->status)->toBe(ApplicationStatus::Submitted);
});

test('reporting the same result twice never skips the next scholarship', function () {
    $first = lifecycleScholarship('Beasiswa Satu', 5);
    $second = lifecycleScholarship('Beasiswa Dua', 10);

    ScholarshipApplication::factory()->submitted()->create([
        'user_id' => $this->mentee->id,
        'scholarship_id' => $first->id,
    ]);

    ScholarshipApplication::factory()->submitted()->create([
        'user_id' => $this->mentee->id,
        'scholarship_id' => $second->id,
    ]);

    $this->service->select($this->mentee, [], $first->id);

    $nearest = $this->mentee->scholarshipApplications()->where('scholarship_id', $first->id)->first();

    $this->service->reportResult($this->mentee, $nearest, ApplicationResult::Accepted);
    $this->service->reportResult($this->mentee, $nearest->refresh(), ApplicationResult::Accepted);

    // The second application is still waiting for a result: it was never skipped.
    expect($this->service->activeApplication($this->mentee)?->scholarship_id)->toBe($second->id)
        ->and($this->mentee->scholarshipApplications()->count())->toBe(2)
        ->and($this->mentee->scholarshipApplications()->where('status', ApplicationStatus::ResultReported)->count())->toBe(1);
});

test('a rejected result is stored without any pricing pitch', function () {
    $application = ScholarshipApplication::factory()->submitted()->create([
        'user_id' => $this->mentee->id,
        'scholarship_id' => lifecycleScholarship('Beasiswa Gagal', 10)->id,
    ]);

    $this->service->reportResult($this->mentee, $application, ApplicationResult::Rejected);

    expect($application->refresh()->result)->toBe(ApplicationResult::Rejected)
        ->and($application->qualifiesForTwibbon())->toBeFalse();
});

test('when no scholarship is left the finished one stays primary', function () {
    $application = ScholarshipApplication::factory()->submitted()->primary()->create([
        'user_id' => $this->mentee->id,
        'scholarship_id' => lifecycleScholarship('Beasiswa Tunggal', 10)->id,
    ]);

    $this->service->reportResult($this->mentee, $application, ApplicationResult::Accepted);

    expect($application->refresh()->is_primary)->toBeTrue()
        ->and($this->service->activeApplication($this->mentee))->toBeNull();
});

test('earlier practice responses survive a scholarship switch', function () {
    $fase = Fase::factory()->create();
    $module = Module::factory()->create(['fase_id' => $fase->id, 'modul_order' => 1]);
    $form = PracticeForm::factory()->create(['module_id' => $module->id]);

    $response = $this->mentee->practiceResponses()->create([
        'module_id' => $module->id,
        'practice_form_id' => $form->id,
        'answers' => ['jawaban' => 'Jawaban historis milik saya.'],
        'ai_status' => AiFeedbackStatus::Completed,
        'ai_score' => 80,
        'attempts' => 1,
        'submitted_at' => now(),
    ]);

    $first = lifecycleScholarship('Beasiswa Lama', 5);
    $second = lifecycleScholarship('Beasiswa Baru', 15);

    ScholarshipApplication::factory()->submitted()->create([
        'user_id' => $this->mentee->id,
        'scholarship_id' => $first->id,
    ]);

    ScholarshipApplication::factory()->submitted()->create([
        'user_id' => $this->mentee->id,
        'scholarship_id' => $second->id,
    ]);

    $this->service->setPrimary($this->mentee, $this->mentee->scholarshipApplications()->where('scholarship_id', $second->id)->first());

    expect($response->fresh()->answers)->toBe(['jawaban' => 'Jawaban historis milik saya.'])
        ->and($this->mentee->practiceResponses()->count())->toBe(1);
});
