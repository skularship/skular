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
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response as ClientResponse;
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
