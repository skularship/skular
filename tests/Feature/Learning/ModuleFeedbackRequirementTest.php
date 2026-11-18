<?php

use App\Contracts\ModuleFeedbackServiceInterface;
use App\Contracts\ProgressServiceInterface;
use App\Enums\ActivityName;
use App\Enums\CheckpointStatus;
use App\Livewire\Learning\FeedbackCommitment;
use App\Livewire\Learning\ModulePlayer;
use App\Livewire\Profiling\Wizard;
use App\Models\ActivityLog;
use App\Models\Cluster;
use App\Models\Fase;
use App\Models\Module;
use App\Models\User;
use App\Services\ModuleFeedbackService;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    $fase = Fase::factory()->create(['fase_order' => 1]);
    $this->module = Module::factory()->create(['fase_id' => $fase->id, 'modul_order' => 1]);
    $this->next_module = Module::factory()->create(['fase_id' => $fase->id, 'modul_order' => 2]);
    $this->mentee = User::factory()->profiled()->create();
});

/**
 * A valid answer set for the module feedback form.
 *
 * @return array<string, mixed>
 */
function validModuleFeedback(array $overrides = []): array
{
    return array_merge([
        'helpful_score' => 5,
        'difficulty' => 'tidak_ada',
        'open_answer' => 'Contoh esainya membantu aku menyusun cerita pribadi.',
        'low_score_reason' => '',
    ], $overrides);
}

test('the module cannot be completed without the feedback, in the player and in the service', function () {
    Livewire::actingAs($this->mentee)
        ->test(ModulePlayer::class, ['module' => $this->module, 'section' => 'checkpoint'])
        ->call('confirmCheckpoint')
        ->assertHasErrors('checkpoint')
        ->assertSee('Isi masukan singkat dulu ya, setelah itu modul berikutnya terbuka.')
        ->assertSet('module_completed', false);

    expect(fn () => app(ProgressServiceInterface::class)->completeModule($this->mentee, $this->module))
        ->toThrow(ValidationException::class, 'Isi masukan singkat dulu ya');

    expect(app(ProgressServiceInterface::class)->checkpointFor($this->mentee, $this->next_module)->status)
        ->toBe(CheckpointStatus::Locked);
});

test('"Kirim masukan & lanjut" stores the feedback and unlocks the next module in one click', function () {
    Livewire::actingAs($this->mentee)
        ->test(ModulePlayer::class, ['module' => $this->module, 'section' => 'checkpoint'])
        ->assertSee('Kirim masukan & lanjut')
        ->set(validModuleFeedback())
        ->call('submitFeedbackAndContinue')
        ->assertHasNoErrors()
        ->assertSet('module_completed', true);

    expect(app(ModuleFeedbackServiceInterface::class)->hasSubmitted($this->mentee, $this->module))->toBeTrue()
        ->and(ActivityLog::query()->where('name', ActivityName::FeedbackSubmitted)->count())->toBe(1)
        ->and(ActivityLog::query()->where('name', ActivityName::ModuleCompleted)->count())->toBe(1);
});

test('a score of 1 to 3 requires a reason, a higher score does not', function () {
    Livewire::actingAs($this->mentee)
        ->test(ModulePlayer::class, ['module' => $this->module, 'section' => 'checkpoint'])
        ->set(validModuleFeedback(['helpful_score' => 3]))
        ->assertSee('Apa yang bikin kurang membantu?')
        ->call('submitFeedbackAndContinue')
        ->assertHasErrors(['low_score_reason' => 'required'])
        ->assertSet('module_completed', false)
        ->set('low_score_reason', 'Materinya terlalu umum untuk jurusan teknik.')
        ->call('submitFeedbackAndContinue')
        ->assertHasNoErrors()
        ->assertSet('module_completed', true);

    $feedback = $this->mentee->moduleFeedbacks()->sole();

    expect($feedback->helpful_score)->toBe(3)
        ->and($feedback->low_score_reason)->toBe('Materinya terlalu umum untuk jurusan teknik.');
});

test('junk answers made of repeated characters or punctuation are rejected', function (string $junk) {
    expect(fn () => app(ModuleFeedbackServiceInterface::class)->submit($this->mentee, $this->module, validModuleFeedback(['open_answer' => $junk])))
        ->toThrow(ValidationException::class, 'Tulis jawaban yang sungguhan ya');

    expect($this->mentee->moduleFeedbacks()->count())->toBe(0);
})->with([
    'one letter' => ['aaaaaaaaaaaaaaaaaaaaaaaa'],
    'keyboard mash' => ['asdasdasdasdasdasdasdasd'],
    'laughing' => ['hahahahahahahahahahahaha'],
    'punctuation' => ['......................!!!'],
]);

test('answers shorter than 20 characters are rejected', function () {
    expect(fn () => app(ModuleFeedbackServiceInterface::class)->submit($this->mentee, $this->module, validModuleFeedback(['open_answer' => 'Bagus sekali.'])))
        ->toThrow(ValidationException::class);
});

test('a module completed before the mandatory form stays completed and is not asked again', function () {
    $this->mentee->checkpoints()->create([
        'module_id' => $this->module->id,
        'fase_id' => $this->module->fase_id,
        'status' => CheckpointStatus::Completed,
        'attempts' => 1,
        'available_at' => now()->subWeek(),
        'started_at' => now()->subWeek(),
        'completed_at' => now()->subWeek(),
    ]);

    expect(app(ProgressServiceInterface::class)->completeModule($this->mentee, $this->module)->status)
        ->toBe(CheckpointStatus::Completed)
        ->and(app(ProgressServiceInterface::class)->checkpointFor($this->mentee, $this->next_module)->status)
        ->toBe(CheckpointStatus::Available);

    Livewire::actingAs($this->mentee)
        ->test(ModulePlayer::class, ['module' => $this->next_module])
        ->assertOk();
});

test('the open question rotates by module order and the admin question wins', function () {
    $service = app(ModuleFeedbackServiceInterface::class);

    expect($service->openQuestionFor(Module::factory()->make(['modul_order' => 3])))->toBe(ModuleFeedbackService::OPEN_QUESTIONS[0])
        ->and($service->openQuestionFor(Module::factory()->make(['modul_order' => 4])))->toBe(ModuleFeedbackService::OPEN_QUESTIONS[1])
        ->and($service->openQuestionFor(Module::factory()->make(['modul_order' => 4, 'feedback_question' => 'Apa contoh yang kurang?'])))->toBe('Apa contoh yang kurang?');

    $service->submit($this->mentee, $this->module, validModuleFeedback());

    expect($this->mentee->moduleFeedbacks()->sole()->open_question)->toBe(ModuleFeedbackService::OPEN_QUESTIONS[1]);
});

test('a module does not open before the mentee agreed to the commitment', function () {
    $newcomer = User::factory()->profiled()->withoutFeedbackCommitment()->create();

    $this->actingAs($newcomer)
        ->get(route('modules.show', $this->module))
        ->assertRedirect(route('feedback.commitment', ['modul' => $this->module->id]));

    Livewire::actingAs($newcomer)
        ->withQueryParams(['modul' => $this->module->id])
        ->test(FeedbackCommitment::class)
        ->assertSee('Kamu masuk program akses gratis Skular')
        ->call('accept')
        ->assertRedirect(route('modules.show', $this->module));

    expect($newcomer->refresh()->hasFeedbackCommitment())->toBeTrue();

    $this->actingAs($newcomer)
        ->get(route('modules.show', $this->module))
        ->assertOk();
});

test('the commitment is the last step of profiling for a mentee who has not agreed yet', function () {
    $newcomer = User::factory()->withoutFeedbackCommitment()->create();
    $cluster = Cluster::factory()->create();

    Livewire::actingAs($newcomer)
        ->test(Wizard::class)
        ->call('submit', [
            'level' => 'S1',
            'age' => '21',
            'gpa' => '3.75',
            'has_sktm' => '1',
            'desired_level' => 'S2',
            'desired_location' => 'luar_negeri',
            'cluster' => (string) $cluster->id,
            'phone' => '081234567890',
        ])
        ->assertRedirect(route('feedback.commitment'));

    Livewire::actingAs($newcomer)
        ->test(FeedbackCommitment::class)
        ->call('accept')
        ->assertRedirect(route('profiling.recommendation'));
});
