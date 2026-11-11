<?php

use App\Contracts\AiFeedbackServiceInterface;
use App\Data\AiFeedbackData;
use App\Enums\AiFeedbackStatus;
use App\Enums\AiScoreLabel;
use App\Jobs\EvaluatePracticeFeedback;
use App\Livewire\Learning\ModulePlayer;
use App\Models\Fase;
use App\Models\Module;
use App\Models\PracticeForm;
use App\Models\User;
use App\Models\UserPracticeResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

beforeEach(function () {
    $fase = Fase::factory()->create(['fase_order' => 1]);
    $this->module = Module::factory()->create(['fase_id' => $fase->id, 'modul_order' => 1]);
    $this->form = PracticeForm::factory()->create(['module_id' => $this->module->id]);
    $this->mentee = User::factory()->profiled()->create();
});

function gateResponse(object $test, AiFeedbackStatus $status, ?int $score = null): UserPracticeResponse
{
    return $test->mentee->practiceResponses()->create([
        'module_id' => $test->module->id,
        'practice_form_id' => $test->form->id,
        'answers' => ['jawaban' => 'Jawaban saya sendiri.'],
        'ai_status' => $status,
        'ai_score' => $score,
        'ai_feedback' => $status === AiFeedbackStatus::Completed
            ? ['feedback' => 'Sudah runtut.', 'strengths' => [], 'improvements' => [], 'next_steps' => []]
            : null,
        'attempts' => 1,
        'submitted_at' => now(),
    ]);
}

test('opening the checkpoint by url without a saved answer redirects back to practice', function () {
    Livewire::actingAs($this->mentee)
        ->test(ModulePlayer::class, ['module' => $this->module, 'section' => 'checkpoint'])
        ->assertSet('section', 'praktik')
        ->assertSee('Tahap akhir belum terbuka')
        ->assertSee('Simpan jawaban semua latihan praktik dulu');
});

test('the checkpoint stays closed while the ai is still assessing the answer', function () {
    gateResponse($this, AiFeedbackStatus::Processing);

    Livewire::actingAs($this->mentee)
        ->test(ModulePlayer::class, ['module' => $this->module, 'section' => 'praktik'])
        ->assertSee('Jawabanmu sedang dinilai AI')
        ->assertSeeHtml('data-test="checkpoint-step-locked"')
        ->assertSeeHtml('data-test="evaluation-poll"')
        ->call('goToSection', 'checkpoint')
        ->assertSet('section', 'praktik')
        ->call('nextSection')
        ->assertSet('section', 'praktik');
});

test('the checkpoint and module feedback actions are refused on the server while assessment runs', function () {
    gateResponse($this, AiFeedbackStatus::Processing);

    Livewire::actingAs($this->mentee)
        ->test(ModulePlayer::class, ['module' => $this->module, 'section' => 'praktik'])
        ->call('confirmCheckpoint')
        ->assertHasErrors('checkpoint')
        ->assertSet('module_completed', false)
        ->set('ratings', ['Materi mudah dipahami'])
        ->call('saveModuleFeedback')
        ->assertHasErrors('module_feedback');

    expect($this->mentee->moduleFeedbacks()->count())->toBe(0);
});

test('a failed assessment offers a re-assessment that opens the checkpoint once it succeeds', function () {
    gateResponse($this, AiFeedbackStatus::Failed);

    $this->app->instance(AiFeedbackServiceInterface::class, new class implements AiFeedbackServiceInterface
    {
        public function generate(Module $module, PracticeForm $practice_form, array $answers): AiFeedbackData
        {
            return new AiFeedbackData(score: 90, strengths: ['Kuat.'], improvements: [], feedback: 'Bagus sekali.', next_steps: []);
        }
    });

    Livewire::actingAs($this->mentee)
        ->test(ModulePlayer::class, ['module' => $this->module, 'section' => 'checkpoint'])
        ->assertSet('section', 'praktik')
        ->assertSee('Penilaian AI untuk salah satu jawabanmu belum berhasil')
        ->assertSee('Nilai ulang')
        ->call('retryFeedback', $this->form->id)
        ->assertSee('Sangat baik')
        ->assertDontSeeHtml('data-test="checkpoint-step-locked"')
        ->call('goToSection', 'checkpoint')
        ->assertSet('section', 'checkpoint')
        ->call('confirmCheckpoint')
        ->assertSet('module_completed', true);
});

test('an assessment stuck in processing can be re-assessed so the mentee is never trapped', function () {
    gateResponse($this, AiFeedbackStatus::Processing);

    $this->travel(UserPracticeResponse::STALLED_AFTER_SECONDS + 5)->seconds();

    Livewire::actingAs($this->mentee)
        ->test(ModulePlayer::class, ['module' => $this->module, 'section' => 'praktik'])
        ->assertSee('Nilai ulang')
        ->assertDontSeeHtml('data-test="evaluation-poll"');
});

test('a completed assessment opens the checkpoint and module feedback', function () {
    gateResponse($this, AiFeedbackStatus::Completed, 72);

    Livewire::actingAs($this->mentee)
        ->test(ModulePlayer::class, ['module' => $this->module, 'section' => 'checkpoint'])
        ->assertSet('section', 'checkpoint')
        ->assertSee('Hasil penilaian AI')
        ->assertSee('Baik')
        ->assertDontSee('Rata-rata skor AI')
        ->set('ratings', ['Materi mudah dipahami'])
        ->call('saveModuleFeedback')
        ->assertHasNoErrors();

    expect($this->mentee->moduleFeedbacks()->count())->toBe(1);
});

test('the mentee sees a label instead of the numeric ai score', function () {
    gateResponse($this, AiFeedbackStatus::Completed, 47);

    Livewire::actingAs($this->mentee)
        ->test(ModulePlayer::class, ['module' => $this->module, 'section' => 'praktik'])
        ->assertSeeHtml('data-test="ai-score-label"')
        ->assertSee('Perlu diperbaiki')
        ->assertDontSee('Skor');
});

test('scores map onto four labels', function (int $score, AiScoreLabel $label) {
    expect(AiScoreLabel::fromScore($score))->toBe($label);
})->with([
    [100, AiScoreLabel::SangatBaik],
    [85, AiScoreLabel::SangatBaik],
    [84, AiScoreLabel::Baik],
    [70, AiScoreLabel::Baik],
    [69, AiScoreLabel::Cukup],
    [50, AiScoreLabel::Cukup],
    [49, AiScoreLabel::PerluDiperbaiki],
    [0, AiScoreLabel::PerluDiperbaiki],
]);

test('re-assessing queues the same job as the first evaluation instead of waiting for the ai in the request', function () {
    Queue::fake();
    $response = gateResponse($this, AiFeedbackStatus::Failed);

    Livewire::actingAs($this->mentee)
        ->test(ModulePlayer::class, ['module' => $this->module, 'section' => 'praktik'])
        ->call('retryFeedback', $this->form->id)
        ->assertDispatched('toast', variant: 'info', text: 'Penilaian ulang dimulai. Hasilnya muncul otomatis di halaman ini.')
        ->assertSeeHtml('data-test="evaluation-poll"')
        ->assertSeeHtml('data-test="feedback-pending"');

    Queue::assertPushed(EvaluatePracticeFeedback::class, fn (EvaluatePracticeFeedback $job): bool => $job->response_id === $response->id
        && $job->attempt === 1);

    expect($response->fresh()->ai_status)->toBe(AiFeedbackStatus::Processing)
        ->and($response->fresh()->answers['jawaban'])->toBe('Jawaban saya sendiri.');
});

test('re-assessing while an evaluation still holds the lock only says it is being assessed', function () {
    Queue::fake();
    $response = gateResponse($this, AiFeedbackStatus::Failed);
    Cache::lock('ai-feedback:'.$response->id, 60)->get();

    Livewire::actingAs($this->mentee)
        ->test(ModulePlayer::class, ['module' => $this->module, 'section' => 'praktik'])
        ->call('retryFeedback', $this->form->id)
        ->assertDispatched('toast', variant: 'info', text: 'Jawabanmu sedang dinilai AI. Hasilnya muncul otomatis di halaman ini.')
        ->assertNotDispatched('toast', variant: 'warning');

    Queue::assertNothingPushed();
    expect($response->fresh()->ai_status)->toBe(AiFeedbackStatus::Failed);
});

test('polling backs off while a slow assessment keeps running and shows a wait estimate', function () {
    gateResponse($this, AiFeedbackStatus::Processing);

    Livewire::actingAs($this->mentee)
        ->test(ModulePlayer::class, ['module' => $this->module, 'section' => 'praktik'])
        ->assertSeeHtml('wire:poll.3s')
        ->assertSee('Biasanya selesai dalam 10–30 detik');

    $this->travel(60)->seconds();

    Livewire::actingAs($this->mentee)
        ->test(ModulePlayer::class, ['module' => $this->module, 'section' => 'praktik'])
        ->assertSeeHtml('wire:poll.5s');

    $this->travel(40)->seconds();

    Livewire::actingAs($this->mentee)
        ->test(ModulePlayer::class, ['module' => $this->module, 'section' => 'praktik'])
        ->assertSeeHtml('wire:poll.10s');
});
