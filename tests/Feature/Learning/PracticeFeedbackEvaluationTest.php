<?php

use App\Ai\Agents\PracticeFeedbackAgent;
use App\Contracts\AiFeedbackServiceInterface;
use App\Contracts\PracticeSubmissionServiceInterface;
use App\Data\AiFeedbackData;
use App\Enums\AiFailureReason;
use App\Enums\AiFeedbackStatus;
use App\Exceptions\AiFeedbackException;
use App\Models\Module;
use App\Models\PracticeForm;
use App\Models\User;
use App\Models\UserPracticeResponse;
use App\Services\PracticeSubmissionService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;

/**
 * Synchronous practice evaluation: answers are stored first, then graded in
 * the same request through PracticeSubmissionService, for the first
 * submission and for "Nilai ulang" alike.
 */
beforeEach(function () {
    Sleep::fake();

    $this->mentee = User::factory()->profiled()->create();
    $this->form = PracticeForm::factory()->create(['module_id' => Module::factory()]);
});

/**
 * A controllable AI fake that counts its calls and can fail the first ones.
 */
function evaluationTestAiFake(int $failures = 0, AiFailureReason $reason = AiFailureReason::Unavailable): object
{
    return new class($failures, $reason) implements AiFeedbackServiceInterface
    {
        public int $calls = 0;

        public function __construct(private int $failures, private AiFailureReason $reason) {}

        public function generate(Module $module, PracticeForm $practice_form, array $answers): AiFeedbackData
        {
            $this->calls++;

            if ($this->calls <= $this->failures) {
                throw new AiFeedbackException('Layanan AI tidak dapat dihubungi.', reason: $this->reason);
            }

            return new AiFeedbackData(score: 80, strengths: ['Jawaban runtut.'], improvements: [], feedback: 'Sudah baik.', next_steps: []);
        }
    };
}

test('submitting stores the answers and returns the completed evaluation in the same request', function () {
    $fake = evaluationTestAiFake();
    app()->instance(AiFeedbackServiceInterface::class, $fake);

    $response = app(PracticeSubmissionServiceInterface::class)->submit($this->mentee, $this->form, ['jawaban' => 'Jawaban awal.']);

    expect($fake->calls)->toBe(1)
        ->and($response->ai_status)->toBe(AiFeedbackStatus::Completed)
        ->and($response->ai_feedback['feedback'])->toBe('Sudah baik.')
        ->and($response->ai_score)->toBe(80)
        ->and($response->fresh()->answers['jawaban'])->toBe('Jawaban awal.')
        ->and(UserPracticeResponse::query()->where('user_id', $this->mentee->id)->count())->toBe(1);
});

test('the answers are stored before the provider is called', function () {
    $fake = new class implements AiFeedbackServiceInterface
    {
        public ?UserPracticeResponse $stored = null;

        public function generate(Module $module, PracticeForm $practice_form, array $answers): AiFeedbackData
        {
            $this->stored = UserPracticeResponse::query()->first();

            return new AiFeedbackData(score: 70, strengths: [], improvements: [], feedback: 'Baik.', next_steps: []);
        }
    };

    app()->instance(AiFeedbackServiceInterface::class, $fake);

    app(PracticeSubmissionServiceInterface::class)->submit($this->mentee, $this->form, ['jawaban' => 'Tersimpan dulu.']);

    expect($fake->stored?->answers)->toBe(['jawaban' => 'Tersimpan dulu.'])
        ->and($fake->stored?->ai_status)->toBe(AiFeedbackStatus::Processing);
});

test('the evaluation stores point and explanation items from the agent', function () {
    PracticeFeedbackAgent::fake([[
        'score' => 78,
        'strengths' => [['point' => 'Tujuan jelas', 'explanation' => 'Kamu menyebut tujuan studi dengan spesifik.']],
        'improvements' => [['point' => 'Bukti kurang', 'explanation' => 'Tambahkan satu pengalaman pendukung.']],
        'feedback' => 'Arahnya sudah tepat.',
        'next_steps' => [['point' => 'Tambah data', 'explanation' => 'Sertakan angka dari kegiatanmu.']],
    ]])->preventStrayPrompts();

    $response = app(PracticeSubmissionServiceInterface::class)->submit($this->mentee, $this->form, ['jawaban' => 'Jawaban pribadi saya.']);

    expect($response->ai_status)->toBe(AiFeedbackStatus::Completed)
        ->and($response->answers['jawaban'])->toBe('Jawaban pribadi saya.')
        ->and($response->ai_feedback['strengths'][0])->toBe([
            'point' => 'Tujuan jelas',
            'explanation' => 'Kamu menyebut tujuan studi dengan spesifik.',
        ]);

    PracticeFeedbackAgent::assertPrompted(fn (): bool => true);
});

test('a passing provider hiccup is retried once with a jittered pause before succeeding', function () {
    $fake = evaluationTestAiFake(failures: 1, reason: AiFailureReason::Timeout);
    app()->instance(AiFeedbackServiceInterface::class, $fake);

    $response = app(PracticeSubmissionServiceInterface::class)->submit($this->mentee, $this->form, ['jawaban' => 'Jawaban pertama.']);

    expect($fake->calls)->toBe(PracticeSubmissionService::EVALUATION_ATTEMPTS)
        ->and($response->ai_status)->toBe(AiFeedbackStatus::Completed)
        ->and($response->ai_error)->toBeNull()
        ->and($response->ai_feedback['feedback'])->toBe('Sudah baik.');

    Sleep::assertSleptTimes(1);
    Sleep::assertSlept(fn ($duration): bool => $duration->totalMilliseconds >= PracticeSubmissionService::RETRY_PAUSE_MS[0]
        && $duration->totalMilliseconds <= PracticeSubmissionService::RETRY_PAUSE_MS[1]);
});

test('a provider failing on every attempt leaves a failed status with a friendly message and the answers kept', function () {
    $fake = evaluationTestAiFake(failures: 99);
    app()->instance(AiFeedbackServiceInterface::class, $fake);

    $response = app(PracticeSubmissionServiceInterface::class)->submit($this->mentee, $this->form, ['jawaban' => 'Jawaban tetap aman.']);

    expect($fake->calls)->toBe(PracticeSubmissionService::EVALUATION_ATTEMPTS)
        ->and($response->ai_status)->toBe(AiFeedbackStatus::Failed)
        ->and($response->ai_error)->toBe('Layanan AI tidak dapat dihubungi.')
        ->and($response->fresh()->answers['jawaban'])->toBe('Jawaban tetap aman.')
        ->and($response->canRetryEvaluation())->toBeTrue()
        ->and(app(PracticeSubmissionServiceInterface::class)->isEvaluationRunning($response))->toBeFalse();
});

test('a configuration error is not retried because it fails the same way every time', function () {
    $fake = evaluationTestAiFake(failures: 99, reason: AiFailureReason::Configuration);
    app()->instance(AiFeedbackServiceInterface::class, $fake);

    $response = app(PracticeSubmissionServiceInterface::class)->submit($this->mentee, $this->form, ['jawaban' => 'Jawaban.']);

    expect($fake->calls)->toBe(1)
        ->and($response->ai_status)->toBe(AiFeedbackStatus::Failed);

    Sleep::assertNeverSlept();
});

test('a submission while another request holds the evaluation lock never calls the provider twice', function () {
    $fake = evaluationTestAiFake();
    app()->instance(AiFeedbackServiceInterface::class, $fake);

    $service = app(PracticeSubmissionServiceInterface::class);
    $first = $service->submit($this->mentee, $this->form, ['jawaban' => 'Klik pertama.']);

    // A double click or a second tab arrives while the first evaluation runs.
    $lock = Cache::lock('ai-feedback:'.$first->id, PracticeSubmissionService::EVALUATION_LOCK_SECONDS);
    $lock->get();

    $second = $service->submit($this->mentee, $this->form, ['jawaban' => 'Klik kedua.']);

    expect($fake->calls)->toBe(1)
        ->and($second->ai_status)->toBe(AiFeedbackStatus::Processing)
        ->and($second->fresh()->answers['jawaban'])->toBe('Klik kedua.')
        ->and($service->isEvaluationRunning($second))->toBeTrue();

    $lock->release();
});

test('nilai ulang grades a failed submission on the same synchronous path', function () {
    $fake = evaluationTestAiFake(failures: PracticeSubmissionService::EVALUATION_ATTEMPTS);
    app()->instance(AiFeedbackServiceInterface::class, $fake);

    $service = app(PracticeSubmissionServiceInterface::class);
    $response = $service->submit($this->mentee, $this->form, ['jawaban' => 'Jawaban saya.']);

    expect($response->ai_status)->toBe(AiFeedbackStatus::Failed);

    Log::spy();

    $retried = $service->retryAiFeedback($response);

    expect($fake->calls)->toBe(PracticeSubmissionService::EVALUATION_ATTEMPTS + 1)
        ->and($retried->ai_status)->toBe(AiFeedbackStatus::Completed)
        ->and($retried->attempts)->toBe(1)
        ->and($retried->answers['jawaban'])->toBe('Jawaban saya.');

    Log::shouldNotHaveReceived('warning', fn (string $message): bool => $message === 'ai.feedback.evaluation_aborted');
});

test('nilai ulang is refused for a completed or still running evaluation', function (AiFeedbackStatus $status) {
    $fake = evaluationTestAiFake();
    app()->instance(AiFeedbackServiceInterface::class, $fake);

    $response = UserPracticeResponse::factory()->create([
        'user_id' => $this->mentee->id,
        'module_id' => $this->form->module_id,
        'practice_form_id' => $this->form->id,
        'ai_status' => $status,
        'attempts' => 1,
    ]);

    $returned = app(PracticeSubmissionServiceInterface::class)->retryAiFeedback($response);

    expect($fake->calls)->toBe(0)
        ->and($returned->ai_status)->toBe($status)
        ->and($response->fresh()->ai_status)->toBe($status);
})->with([
    'sudah dinilai' => [AiFeedbackStatus::Completed],
    'masih dinilai' => [AiFeedbackStatus::Processing],
]);

test('every provider call writes one metadata-only attempt log line', function () {
    Log::spy();

    PracticeFeedbackAgent::fake([[
        'score' => 70,
        'strengths' => [['point' => 'Tujuan jelas', 'explanation' => 'Tujuan studimu spesifik.']],
        'improvements' => [],
        'feedback' => 'Arahnya sudah tepat.',
        'next_steps' => [],
    ]])->preventStrayPrompts();

    $response = app(PracticeSubmissionServiceInterface::class)->submit($this->mentee, $this->form, ['jawaban' => 'Cerita pribadi yang rahasia.']);

    Log::shouldHaveReceived('info')->withArgs(
        fn (string $message, array $context = []): bool => $message === 'ai.feedback.attempt'
            && $context['ai_response_id'] === $response->id
            && $context['ai_attempt'] === 1
            && $context['ai_trigger'] === 'submit'
            && $context['outcome'] === 'completed'
            && is_int($context['duration_ms'])
            && $context['prompt_chars'] > 0
            && ! str_contains((string) json_encode($context), 'rahasia'),
    )->once();
});
