<?php

use App\Ai\Agents\PracticeFeedbackAgent;
use App\Contracts\AiFeedbackServiceInterface;
use App\Enums\AiFailureReason;
use App\Exceptions\AiFeedbackException;
use App\Models\Module;
use App\Models\PracticeForm;
use App\Models\User;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response as ClientResponse;
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
