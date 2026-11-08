<?php

use App\Ai\Agents\ModuleBrainstormingAgent;
use App\Ai\Agents\ModuleMentorAgent;
use App\Ai\Agents\PracticeFeedbackAgent;
use App\Contracts\AiFeedbackServiceInterface;
use App\Contracts\ModuleMentorServiceInterface;
use App\Enums\AiFailureReason;
use App\Enums\AiFeedbackStatus;
use App\Exceptions\AiConfigurationException;
use App\Exceptions\AiFeedbackException;
use App\Exceptions\AiMentorException;
use App\Jobs\EvaluatePracticeFeedback;
use App\Models\Module;
use App\Models\PracticeForm;
use App\Models\User;
use App\Models\UserPracticeResponse;
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

test('a crashed evaluation job marks the response as failed so the mentee can retry', function () {
    $response = UserPracticeResponse::factory()->create([
        'practice_form_id' => $this->form->id,
        'module_id' => $this->module->id,
        'ai_status' => AiFeedbackStatus::Processing,
    ]);

    (new EvaluatePracticeFeedback($response->id, $this->form->id))->failed(new RuntimeException('worker killed'));

    $response->refresh();

    expect($response->ai_status)->toBe(AiFeedbackStatus::Failed)
        ->and($response->ai_error)->toContain('coba nilai ulang')
        ->and($response->canRetryEvaluation())->toBeTrue();
});

test('a crashed job never overwrites a completed evaluation', function () {
    $response = UserPracticeResponse::factory()->create([
        'practice_form_id' => $this->form->id,
        'module_id' => $this->module->id,
        'ai_status' => AiFeedbackStatus::Completed,
    ]);

    (new EvaluatePracticeFeedback($response->id, $this->form->id))->failed(null);

    expect($response->refresh()->ai_status)->toBe(AiFeedbackStatus::Completed);
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
