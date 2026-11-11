<?php

use App\Ai\Agents\PracticeFeedbackAgent;
use App\Contracts\AiFeedbackServiceInterface;
use App\Contracts\PracticeSubmissionServiceInterface;
use App\Data\AiFeedbackData;
use App\Enums\AiFeedbackStatus;
use App\Jobs\EvaluatePracticeFeedback;
use App\Models\Module;
use App\Models\PracticeForm;
use App\Models\User;
use App\Models\UserPracticeResponse;
use Illuminate\Support\Facades\Queue;

/**
 * A controllable AI fake that can pretend the provider is down.
 */
function queueTestAiFake(): object
{
    return new class implements AiFeedbackServiceInterface
    {
        public function generate(Module $module, PracticeForm $practice_form, array $answers): AiFeedbackData
        {
            return new AiFeedbackData(
                score: 75,
                strengths: ['Jawaban runtut.'],
                improvements: [],
                feedback: 'Feedback tersusun.',
                next_steps: [],
            );
        }
    };
}

test('submitting a practice form leaves the AI evaluation to the queue', function () {
    Queue::fake();

    app()->instance(AiFeedbackServiceInterface::class, queueTestAiFake());

    $user = User::factory()->profiled()->create();
    $form = PracticeForm::factory()->create(['module_id' => Module::factory()]);

    $response = app(PracticeSubmissionServiceInterface::class)->submit($user, $form, ['jawaban' => 'Jawaban awal.']);

    // The row is durable and Processing even though no vendor call has been
    // made: the web request no longer waits for the provider.
    Queue::assertPushed(EvaluatePracticeFeedback::class, function (EvaluatePracticeFeedback $job) use ($response, $form): bool {
        return $job->response_id === $response->id && $job->practice_form_id === $form->id;
    });
    Queue::assertPushed(EvaluatePracticeFeedback::class, fn (EvaluatePracticeFeedback $job) => $job->afterCommit);

    expect($response->answers['jawaban'])->toBe('Jawaban awal.')
        ->and($response->ai_status)->toBe(AiFeedbackStatus::Processing);
});

test('running the queued job evaluates the stored submission', function () {
    $feedback = queueTestAiFake();
    app()->instance(AiFeedbackServiceInterface::class, $feedback);

    $user = User::factory()->profiled()->create();
    $form = PracticeForm::factory()->create(['module_id' => Module::factory()]);

    $response = app(PracticeSubmissionServiceInterface::class)->submit($user, $form, ['jawaban' => 'Jawaban yang benar.']);
    $response->refresh();

    // With the synchronous test queue the job already ran at dispatch time.
    expect($response->ai_status)->toBe(AiFeedbackStatus::Completed)
        ->and($response->ai_feedback['feedback'])->toBe('Feedback tersusun.');

    // Re-running the job is idempotent: no double feedback, no double billing.
    (new EvaluatePracticeFeedback($response->id, $form->id))->handle(app(PracticeSubmissionServiceInterface::class));

    $response->refresh();

    expect($response->ai_status)->toBe(AiFeedbackStatus::Completed);
    expect(UserPracticeResponse::query()->where('user_id', $user->id)->where('practice_form_id', $form->id)->count())->toBe(1);
});

test('a replayed job never re-bills the provider for a completed evaluation', function () {
    $fake = new class implements AiFeedbackServiceInterface
    {
        public int $calls = 0;

        public function generate(Module $module, PracticeForm $practice_form, array $answers): AiFeedbackData
        {
            $this->calls++;

            return new AiFeedbackData(score: 90, strengths: [], improvements: [], feedback: 'Baik.', next_steps: []);
        }
    };

    app()->instance(AiFeedbackServiceInterface::class, $fake);

    $user = User::factory()->profiled()->create();
    $form = PracticeForm::factory()->create(['module_id' => Module::factory()]);

    $service = app(PracticeSubmissionServiceInterface::class);
    $response = $service->submit($user, $form, ['jawaban' => 'Jawaban.']);

    $service->evaluateSubmission($response->id, $form->id);
    $service->evaluateSubmission($response->id, $form->id);

    expect($fake->calls)->toBe(1)
        ->and($response->fresh()->ai_score)->toBe(90);
});

test('a missing submission row is ignored by the job', function () {
    $service = app(PracticeSubmissionServiceInterface::class);

    expect(fn () => $service->evaluateSubmission(999999, 999999))->not->toThrow(Throwable::class);
});

test('the queued job stores point and explanation items and stays idempotent', function () {
    PracticeFeedbackAgent::fake([[
        'score' => 78,
        'strengths' => [['point' => 'Tujuan jelas', 'explanation' => 'Kamu menyebut tujuan studi dengan spesifik.']],
        'improvements' => [['point' => 'Bukti kurang', 'explanation' => 'Tambahkan satu pengalaman pendukung.']],
        'feedback' => 'Arahnya sudah tepat.',
        'next_steps' => [['point' => 'Tambah data', 'explanation' => 'Sertakan angka dari kegiatanmu.']],
    ]])->preventStrayPrompts();

    $user = User::factory()->profiled()->create();
    $form = PracticeForm::factory()->create(['module_id' => Module::factory()]);

    $service = app(PracticeSubmissionServiceInterface::class);
    $response = $service->submit($user, $form, ['jawaban' => 'Jawaban pribadi saya.']);

    // A replay must not reach the (fake) provider again; preventStrayPrompts would throw.
    (new EvaluatePracticeFeedback($response->id, $form->id))->handle($service);

    $response->refresh();

    expect($response->ai_status)->toBe(AiFeedbackStatus::Completed)
        ->and($response->answers['jawaban'])->toBe('Jawaban pribadi saya.')
        ->and($response->ai_feedback['strengths'][0])->toBe([
            'point' => 'Tujuan jelas',
            'explanation' => 'Kamu menyebut tujuan studi dengan spesifik.',
        ]);

    PracticeFeedbackAgent::assertPrompted(fn (): bool => true);
});
