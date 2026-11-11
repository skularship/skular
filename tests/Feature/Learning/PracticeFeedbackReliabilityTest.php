<?php

use App\Ai\Agents\PracticeFeedbackAgent;
use App\Contracts\AiFeedbackServiceInterface;
use App\Contracts\PracticeSubmissionServiceInterface;
use App\Data\AiFeedbackData;
use App\Enums\AiFailureReason;
use App\Enums\AiFeedbackStatus;
use App\Exceptions\AiFeedbackException;
use App\Jobs\EvaluatePracticeFeedback;
use App\Models\Module;
use App\Models\PracticeForm;
use App\Models\User;
use App\Models\UserPracticeResponse;
use App\Services\PracticeSubmissionService;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Illuminate\Bus\UniqueLock;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response as ClientResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Sleep;
use Laravel\Ai\Exceptions\ProviderConnectionException;

/**
 * Regression coverage for the "first evaluation fails, Nilai ulang succeeds"
 * report: classification of real SDK errors, stale locks, partial model output
 * and a resubmission racing a running evaluation.
 */
beforeEach(function () {
    Sleep::fake();

    $this->mentee = User::factory()->profiled()->create();
    $this->module = Module::factory()->create();
    $this->form = PracticeForm::factory()->create(['module_id' => $this->module->id]);
});

/**
 * An AI fake that records every answer it grades and echoes it in the summary.
 */
function reliabilityAiFake(): object
{
    return new class implements AiFeedbackServiceInterface
    {
        /** @var array<int, string> */
        public array $graded = [];

        public ?Closure $while_grading = null;

        public function generate(Module $module, PracticeForm $practice_form, array $answers): AiFeedbackData
        {
            $this->graded[] = (string) ($answers['jawaban'] ?? '');

            if ($this->while_grading !== null) {
                $callback = $this->while_grading;
                $this->while_grading = null;
                $callback();
            }

            return new AiFeedbackData(
                score: 80,
                strengths: [['point' => 'Runtut', 'explanation' => 'Alurnya jelas.']],
                improvements: [],
                feedback: 'Untuk: '.($answers['jawaban'] ?? ''),
                next_steps: [],
            );
        }
    };
}

test('a provider timeout wrapped by the sdk is classified as a timeout', function () {
    // This is the exact chain the OpenRouter gateway throws when #[Timeout] elapses.
    $exception = ProviderConnectionException::forProvider(
        'openrouter',
        0,
        new ConnectionException('cURL error 28: Operation timed out after 25001 milliseconds with 0 bytes received'),
    );

    expect(AiFailureReason::fromThrowable($exception))->toBe(AiFailureReason::Timeout);
});

test('a refused connection wrapped by the sdk stays unavailable', function () {
    $exception = ProviderConnectionException::forProvider(
        'openrouter',
        0,
        new ConnectionException('cURL error 7: Failed to connect to openrouter.ai port 443'),
    );

    expect(AiFailureReason::fromThrowable($exception))->toBe(AiFailureReason::Unavailable);
});

test('provider http errors that repeat on every retry are configuration problems', function (int $status, AiFailureReason $reason) {
    $exception = new RequestException(new ClientResponse(new GuzzleResponse($status)));

    expect(AiFailureReason::fromThrowable($exception))->toBe($reason);
})->with([
    'kunci salah' => [401, AiFailureReason::Configuration],
    'model tidak dikenal' => [404, AiFailureReason::Configuration],
    'request timeout' => [408, AiFailureReason::Timeout],
    'server error' => [500, AiFailureReason::Unavailable],
]);

test('a unique job lock left by a dead worker never outlives the stall window', function () {
    $fake = reliabilityAiFake();
    app()->instance(AiFeedbackServiceInterface::class, $fake);

    $response = UserPracticeResponse::factory()->create([
        'user_id' => $this->mentee->id,
        'module_id' => $this->module->id,
        'practice_form_id' => $this->form->id,
        'ai_status' => AiFeedbackStatus::Processing,
        'attempts' => 1,
    ]);

    // A worker that died mid-job never runs failed(), so its unique lock stays behind.
    (new UniqueLock(app(CacheRepository::class)))->acquire(new EvaluatePracticeFeedback($response->id, $this->form->id, 1));

    // Once the mentee is offered "Nilai ulang", a new evaluation must be accepted.
    $this->travel(UserPracticeResponse::STALLED_AFTER_SECONDS + 1)->seconds();

    app(PracticeSubmissionServiceInterface::class)->retryAiFeedback($response->fresh());

    expect($fake->graded)->toHaveCount(1)
        ->and($response->fresh()->ai_status)->toBe(AiFeedbackStatus::Completed);
});

test('a resubmission after a worker died mid-job is still evaluated', function () {
    $fake = reliabilityAiFake();
    app()->instance(AiFeedbackServiceInterface::class, $fake);

    $response = UserPracticeResponse::factory()->create([
        'user_id' => $this->mentee->id,
        'module_id' => $this->module->id,
        'practice_form_id' => $this->form->id,
        'answers' => ['jawaban' => 'Versi pertama.'],
        'ai_status' => AiFeedbackStatus::Processing,
        'attempts' => 1,
    ]);

    // The queued job for the first answer was taken by a worker that then died:
    // its unique lock was acquired at dispatch and is never released.
    (new UniqueLock(app(CacheRepository::class)))->acquire(new EvaluatePracticeFeedback($response->id, $this->form->id, 1));

    app(PracticeSubmissionServiceInterface::class)->submit($this->mentee, $this->form, ['jawaban' => 'Versi kedua.']);

    expect($fake->graded)->toBe(['Versi kedua.'])
        ->and($response->fresh()->ai_status)->toBe(AiFeedbackStatus::Completed);
});

test('a retry while another evaluation holds the lock leaves the stored state untouched', function () {
    $fake = reliabilityAiFake();
    app()->instance(AiFeedbackServiceInterface::class, $fake);

    $response = UserPracticeResponse::factory()->create([
        'user_id' => $this->mentee->id,
        'module_id' => $this->module->id,
        'practice_form_id' => $this->form->id,
        'ai_status' => AiFeedbackStatus::Failed,
        'ai_error' => 'Layanan AI tidak dapat dihubungi.',
    ]);

    Cache::lock('ai-feedback:'.$response->id, 60)->get();

    app(PracticeSubmissionServiceInterface::class)->retryAiFeedback($response->fresh());

    // No provider call was made, so the response must not claim one is running.
    expect($fake->graded)->toBe([])
        ->and($response->fresh()->ai_status)->toBe(AiFeedbackStatus::Failed);
});

test('a resubmission during a running evaluation is graded on the newest answers', function () {
    $fake = reliabilityAiFake();
    app()->instance(AiFeedbackServiceInterface::class, $fake);

    $service = app(PracticeSubmissionServiceInterface::class);

    // The mentee edits and sends again while the first answer is being graded.
    $fake->while_grading = fn () => $service->submit($this->mentee, $this->form, ['jawaban' => 'Versi kedua.']);

    $response = $service->submit($this->mentee, $this->form, ['jawaban' => 'Versi pertama.']);
    $response->refresh();

    expect($response->answers['jawaban'])->toBe('Versi kedua.')
        ->and($response->attempts)->toBe(2)
        ->and($response->ai_status)->toBe(AiFeedbackStatus::Completed)
        ->and($response->ai_feedback['feedback'])->toBe('Untuk: Versi kedua.');
});

test('a model answer without a summary but with points is still accepted', function () {
    PracticeFeedbackAgent::fake([[
        'score' => 72,
        'strengths' => [['point' => 'Tujuan jelas', 'explanation' => 'Tujuan studimu spesifik.']],
        'improvements' => [],
        'feedback' => '',
        'next_steps' => [],
    ]])->preventStrayPrompts();

    $feedback = app(AiFeedbackServiceInterface::class)->generate($this->module, $this->form, ['jawaban' => 'Jawaban saya.']);

    expect($feedback->normalizedScore())->toBe(72)
        ->and($feedback->strengths)->toHaveCount(1);
});

test('a model answer without a readable score is rejected instead of graded as zero', function () {
    PracticeFeedbackAgent::fake([[
        'strengths' => [['point' => 'Tujuan jelas', 'explanation' => 'Tujuan studimu spesifik.']],
        'improvements' => [],
        'feedback' => 'Arahnya sudah tepat.',
        'next_steps' => [],
    ]])->preventStrayPrompts();

    try {
        app(AiFeedbackServiceInterface::class)->generate($this->module, $this->form, ['jawaban' => 'Jawaban saya.']);
        $this->fail('An evaluation without a score must not be graded.');
    } catch (AiFeedbackException $exception) {
        expect($exception->reason)->toBe(AiFailureReason::InvalidResponse)
            ->and($exception->isTransient())->toBeTrue();
    }
});

test('the evaluation time budget stays consistent across agent, job, queue, lock and stall window', function () {
    $agent_timeout = (new ReflectionClass(PracticeFeedbackAgent::class))
        ->getAttributes(Laravel\Ai\Attributes\Timeout::class)[0]->newInstance()->value;
    $job = new EvaluatePracticeFeedback(1, 1, 1);
    $longest_pause_seconds = PracticeSubmissionService::RATE_LIMITED_PAUSE_MS[1] / 1000;
    $retry_after = config('queue.connections.database.retry_after');

    expect($agent_timeout)->toBe(PracticeFeedbackAgent::TIMEOUT_SECONDS)
        // Every queued attempt fits inside one job run...
        ->and($agent_timeout * PracticeSubmissionService::QUEUED_EVALUATION_ATTEMPTS + $longest_pause_seconds)->toBeLessThan($job->timeout)
        // ...which is never reserved twice by the database queue...
        ->and($job->timeout)->toBeLessThan($retry_after)
        // ...and the provider lock outlives the whole run.
        ->and(PracticeSubmissionService::EVALUATION_LOCK_SECONDS)->toBeGreaterThanOrEqual($job->timeout)
        ->and($job->uniqueFor)->toBeGreaterThanOrEqual(PracticeSubmissionService::EVALUATION_LOCK_SECONDS)
        // Once "Nilai ulang" appears, no lock from a dead worker can block it.
        ->and(UserPracticeResponse::STALLED_AFTER_SECONDS)->toBeGreaterThanOrEqual($job->uniqueFor);
});

test('a slow but healthy evaluation keeps its heartbeat and is never flagged as stalled', function () {
    $fake = reliabilityAiFake();
    app()->instance(AiFeedbackServiceInterface::class, $fake);

    Queue::fake();
    $response = app(PracticeSubmissionServiceInterface::class)->submit($this->mentee, $this->form, ['jawaban' => 'Jawaban.']);

    // The job is picked up late, close to the stall window...
    $this->travel(UserPracticeResponse::STALLED_AFTER_SECONDS - 10)->seconds();

    $observed = null;
    $fake->while_grading = function () use ($response, &$observed) {
        // ...and the provider is slow too: far past the window since the
        // submission, but the heartbeat written when the attempt started keeps it alive.
        $this->travel(UserPracticeResponse::STALLED_AFTER_SECONDS - 10)->seconds();
        $observed = $response->fresh()->isStalled();
    };

    app(PracticeSubmissionServiceInterface::class)->evaluateSubmission($response->id, $this->form->id, $response->attempts);

    expect($observed)->toBeFalse()
        ->and($response->fresh()->ai_status)->toBe(AiFeedbackStatus::Completed);
});
