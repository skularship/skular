<?php

use App\Ai\Agents\ModuleBrainstormingAgent;
use App\Ai\Agents\ModuleMentorAgent;
use App\Ai\Agents\PracticeFeedbackAgent;
use App\Contracts\AiFeedbackServiceInterface;
use App\Contracts\ModuleMentorServiceInterface;
use App\Contracts\PracticeSubmissionServiceInterface;
use App\Data\AiFeedbackData;
use App\Enums\AiFailureReason;
use App\Enums\AiFeedbackStatus;
use App\Exceptions\AiConfigurationException;
use App\Exceptions\AiFeedbackException;
use App\Exceptions\AiMentorException;
use App\Models\Module;
use App\Models\PracticeForm;
use App\Models\User;
use App\Models\UserPracticeResponse;
use App\Services\PracticeSubmissionService;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Attributes\Model as ModelAttribute;
use Laravel\Ai\Attributes\Provider as ProviderAttribute;
use Laravel\Ai\Attributes\UseCheapestModel;
use Laravel\Ai\Attributes\UseSmartestModel;
use Laravel\Ai\Exceptions\RateLimitedException;

beforeEach(function () {
    $this->module = Module::factory()->create();
    $this->form = PracticeForm::factory()->create(['module_id' => $this->module->id]);
});

test('no ai agent pins its own provider or model', function (string $agent) {
    $reflection = new ReflectionClass($agent);

    foreach ([ModelAttribute::class, ProviderAttribute::class, UseCheapestModel::class, UseSmartestModel::class] as $attribute) {
        expect($reflection->getAttributes($attribute))->toBeEmpty();
    }

    expect($reflection->hasMethod('model'))->toBeFalse()
        ->and($reflection->hasMethod('provider'))->toBeFalse();
})->with([
    ModuleMentorAgent::class,
    ModuleBrainstormingAgent::class,
    PracticeFeedbackAgent::class,
]);

test('provider failures are classified for friendly messages', function (Throwable $exception, AiFailureReason $reason) {
    expect(AiFailureReason::fromThrowable($exception))->toBe($reason);
})->with([
    'konfigurasi' => [new AiConfigurationException('kosong'), AiFailureReason::Configuration],
    'rate limit' => [RateLimitedException::forProvider('openrouter'), AiFailureReason::RateLimited],
    'timeout' => [new RuntimeException('cURL error 28: Operation timed out'), AiFailureReason::Timeout],
    'lainnya' => [new RuntimeException('boom'), AiFailureReason::Unavailable],
]);

test('a rate limited mentor request asks the mentee to wait and is logged', function () {
    Log::spy();
    ModuleMentorAgent::fake([fn () => throw RateLimitedException::forProvider('openrouter')]);

    expect(fn () => app(ModuleMentorServiceInterface::class)->reply($this->module, $this->form, 'Bantu saya.'))
        ->toThrow(AiMentorException::class, 'Tunggu sekitar satu menit');

    Log::shouldHaveReceived('warning')->withArgs(fn (string $message, array $context): bool => $message === 'AI Mentor request failed'
        && $context['reason'] === 'rate_limited');
});

test('an empty mentor answer becomes a friendly retryable error', function () {
    ModuleMentorAgent::fake(['   ']);

    expect(fn () => app(ModuleMentorServiceInterface::class)->reply($this->module, $this->form, 'Bantu saya.'))
        ->toThrow(AiMentorException::class, 'coba kirim lagi');
});

test('a rate limited evaluation keeps the answer and suggests a retry', function () {
    PracticeFeedbackAgent::fake([fn () => throw RateLimitedException::forProvider('openrouter')]);

    expect(fn () => app(AiFeedbackServiceInterface::class)->generate($this->module, $this->form, ['jawaban' => 'Jawaban saya.']))
        ->toThrow(AiFeedbackException::class, 'Jawabanmu tetap tersimpan');
});

test('the mentor is instructed to answer briefly with the PREP structure', function () {
    $instructions = (new ModuleMentorAgent($this->module))->instructions();

    expect($instructions)->toContain('PREP')
        ->and($instructions)->toContain('120 kata')
        ->and($instructions)->toContain('"kamu"');
});

/**
 * An AI fake that runs an optional side effect, then crashes with an
 * unexpected (non AiFeedbackException) error, as a bug or a lost database
 * connection would.
 */
function crashingAiFake(?Closure $before_crash = null): object
{
    return new class($before_crash) implements AiFeedbackServiceInterface
    {
        public int $calls = 0;

        public function __construct(private ?Closure $before_crash) {}

        public function generate(Module $module, PracticeForm $practice_form, array $answers): AiFeedbackData
        {
            $this->calls++;

            if ($this->before_crash !== null) {
                ($this->before_crash)();
            }

            throw new LogicException('unexpected crash');
        }
    };
}

test('an unexpected error during the evaluation fails the response and releases the lock', function () {
    $fake = crashingAiFake();
    app()->instance(AiFeedbackServiceInterface::class, $fake);

    $service = app(PracticeSubmissionServiceInterface::class);
    $response = $service->submit(User::factory()->profiled()->create(), $this->form, ['jawaban' => 'Jawaban aman.']);

    $response->refresh();

    // Not retried like a provider hiccup, never left hanging in processing.
    expect($fake->calls)->toBe(1)
        ->and($response->ai_status)->toBe(AiFeedbackStatus::Failed)
        ->and($response->ai_error)->toBe(PracticeSubmissionService::ABORTED_EVALUATION_MESSAGE)
        ->and($response->answers['jawaban'])->toBe('Jawaban aman.')
        ->and($response->canRetryEvaluation())->toBeTrue()
        ->and($service->isEvaluationRunning($response))->toBeFalse();
});

test('an unexpected error never overwrites an evaluation another request completed', function () {
    $response = null;

    app()->instance(AiFeedbackServiceInterface::class, crashingAiFake(function () use (&$response): void {
        UserPracticeResponse::query()->whereKey($response->id)->update(['ai_status' => AiFeedbackStatus::Completed->value]);
    }));

    $response = UserPracticeResponse::factory()->create([
        'practice_form_id' => $this->form->id,
        'module_id' => $this->module->id,
        'ai_status' => AiFeedbackStatus::Failed,
        'attempts' => 1,
    ]);

    app(PracticeSubmissionServiceInterface::class)->retryAiFeedback($response);

    expect($response->refresh()->ai_status)->toBe(AiFeedbackStatus::Completed);
});

test('an unexpected error while grading old answers never fails a newer submission', function () {
    $mentee = User::factory()->profiled()->create();
    $service = app(PracticeSubmissionServiceInterface::class);

    app()->instance(AiFeedbackServiceInterface::class, crashingAiFake(
        fn () => $service->submit($mentee, $this->form, ['jawaban' => 'Versi kedua.']),
    ));

    // Resolve again so the service uses the crashing fake.
    app(PracticeSubmissionServiceInterface::class)->submit($mentee, $this->form, ['jawaban' => 'Versi pertama.']);

    $response = UserPracticeResponse::query()->where('user_id', $mentee->id)->sole();

    expect($response->attempts)->toBe(2)
        ->and($response->answers['jawaban'])->toBe('Versi kedua.')
        ->and($response->ai_status)->toBe(AiFeedbackStatus::Processing);
});

test('an evaluation stuck in processing becomes retryable after the stall window', function () {
    $response = UserPracticeResponse::factory()->create([
        'user_id' => User::factory(),
        'practice_form_id' => $this->form->id,
        'module_id' => $this->module->id,
        'ai_status' => AiFeedbackStatus::Processing,
    ]);

    expect($response->isEvaluating())->toBeTrue()
        ->and($response->canRetryEvaluation())->toBeFalse();

    $this->travel(UserPracticeResponse::STALLED_AFTER_SECONDS + 1)->seconds();

    expect($response->isEvaluating())->toBeFalse()
        ->and($response->isStalled())->toBeTrue()
        ->and($response->canRetryEvaluation())->toBeTrue();
});
