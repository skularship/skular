<?php

use App\Contracts\ScholarshipApplicationServiceInterface;
use App\Enums\ApplicationResult;
use App\Enums\ApplicationStatus;
use App\Livewire\Scholarships\Tracker;
use App\Livewire\Twibbon\Generator;
use App\Models\Scholarship;
use App\Models\ScholarshipApplication;
use App\Models\TwibbonTemplate;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    $this->mentee = User::factory()->profiled()->create();
    $this->scholarship = Scholarship::factory()->create();
    $this->application = ScholarshipApplication::factory()->create([
        'user_id' => $this->mentee->id,
        'scholarship_id' => $this->scholarship->id,
    ]);
});

test('the tracker shows an empty state without any selection', function () {
    $this->application->delete();

    $this->actingAs($this->mentee)
        ->get(route('scholarships.tracker'))
        ->assertOk()
        ->assertSee('Belum ada beasiswa yang kamu pilih');
});

test('the tracker lists the selected scholarship with its status', function () {
    $this->actingAs($this->mentee)
        ->get(route('scholarships.tracker'))
        ->assertOk()
        ->assertSee($this->scholarship->scholarship_name)
        ->assertSee('Konfirmasi sudah mendaftar');
});

test('confirming a submission requires a screenshot', function () {
    Livewire::actingAs($this->mentee)
        ->test(Tracker::class)
        ->call('openConfirm', $this->application->id)
        ->call('confirmSubmission')
        ->assertHasErrors('proof');

    expect($this->application->refresh()->status)->toBe(ApplicationStatus::Selected);
});

test('confirming a submission stores the screenshot on the private disk', function () {
    Storage::fake('local');

    Livewire::actingAs($this->mentee)
        ->test(Tracker::class)
        ->call('openConfirm', $this->application->id)
        ->set('proof', UploadedFile::fake()->image('bukti.png'))
        ->set('note', 'Sudah submit lewat portal')
        ->call('confirmSubmission')
        ->assertHasNoErrors();

    $application = $this->application->refresh();

    expect($application->status)->toBe(ApplicationStatus::Submitted)
        ->and($application->hasSubmissionProof())->toBeTrue()
        ->and(Storage::disk('local')->exists((string) $application->submission_proof))->toBeTrue()
        ->and(Storage::disk('public')->exists((string) $application->submission_proof))->toBeFalse();
});

test('the submission proof can only be downloaded by its owner', function () {
    Storage::fake('local');

    Storage::disk('local')->put('submissions/1/bukti.png', 'proof-bytes');

    $this->application->update([
        'status' => ApplicationStatus::Submitted,
        'submitted_at' => now(),
        'submission_proof' => 'submissions/1/bukti.png',
        'submission_confirmed_at' => now(),
    ]);

    $this->actingAs($this->mentee)
        ->get(route('applications.proof', $this->application))
        ->assertOk();

    $other = User::factory()->profiled()->create();

    $this->actingAs($other)
        ->get(route('applications.proof', $this->application))
        ->assertForbidden();
});

test('a result cannot be reported before the submission is confirmed', function () {
    $service = app(ScholarshipApplicationServiceInterface::class);

    expect(fn () => $service->reportResult($this->mentee, $this->application, ApplicationResult::Accepted))
        ->toThrow(ValidationException::class);

    $application = $this->application->refresh();

    expect($application->result)->toBeNull()
        ->and($application->status)->toBe(ApplicationStatus::Selected)
        ->and($application->qualifiesForTwibbon())->toBeFalse();
});

test('reporting an accepted result unlocks the twibbon', function () {
    $this->application->update([
        'status' => ApplicationStatus::Submitted,
        'submitted_at' => now(),
        'submission_confirmed_at' => now(),
    ]);

    Livewire::actingAs($this->mentee)
        ->test(Tracker::class)
        ->call('openReport', $this->application->id)
        ->call('reportResult', $this->application->id, 'accepted')
        ->assertSee('Buat twibbon');

    $application = $this->application->refresh();

    expect($application->status)->toBe(ApplicationStatus::ResultReported)
        ->and($application->result)->toBe(ApplicationResult::Accepted);
});

test('reporting a rejected result shows supportive words without any pricing pitch', function () {
    $this->application->update([
        'status' => ApplicationStatus::Submitted,
        'submitted_at' => now(),
        'submission_confirmed_at' => now(),
    ]);

    Livewire::actingAs($this->mentee)
        ->test(Tracker::class)
        ->call('openReport', $this->application->id)
        ->call('reportResult', $this->application->id, 'rejected')
        ->assertSee('Belum berhasil kali ini')
        ->assertDontSee('diskon')
        ->assertDontSee('harga')
        ->assertDontSee('Buat twibbon');

    expect($this->application->refresh()->result)->toBe(ApplicationResult::Rejected);
});

test('the twibbon page is guarded by ownership', function () {
    $this->application->delete();

    $accepted = ScholarshipApplication::factory()->reported(ApplicationResult::Accepted)->create([
        'user_id' => $this->mentee->id,
        'scholarship_id' => $this->scholarship->id,
    ]);

    $other = User::factory()->profiled()->create();

    $this->actingAs($other)
        ->get(route('twibbon.show', $accepted))
        ->assertForbidden();
});

test('a twibbon can only be generated after passing the scholarship', function () {
    $this->actingAs($this->mentee)
        ->get(route('twibbon.show', $this->application))
        ->assertRedirect(route('scholarships.tracker'));
});

test('a twibbon is rendered from an active template', function () {
    Storage::fake('public');

    $canvas = imagecreatetruecolor(400, 400);
    imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));

    ob_start();
    imagepng($canvas);
    $binary = ob_get_clean();
    imagedestroy($canvas);

    Storage::disk('public')->put('twibbon/templates/default.png', (string) $binary);

    TwibbonTemplate::factory()->create(['image_path' => 'twibbon/templates/default.png']);

    $this->application->delete();

    $accepted = ScholarshipApplication::factory()->reported(ApplicationResult::Accepted)->create([
        'user_id' => $this->mentee->id,
        'scholarship_id' => $this->scholarship->id,
    ]);

    Livewire::actingAs($this->mentee)
        ->test(Generator::class, ['application' => $accepted])
        ->call('generate')
        ->assertSee('twibbon-preview');

    $path = $accepted->refresh()->metadata['twibbon_path'] ?? null;

    expect($path)->not->toBeNull()
        ->and(Storage::disk('public')->exists((string) $path))->toBeTrue();
});
