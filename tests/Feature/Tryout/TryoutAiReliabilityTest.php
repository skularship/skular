<?php

use App\Ai\Agents\SpeakingAssessmentAgent;
use App\Ai\Agents\TryoutMentorAgent;
use App\Ai\Agents\WritingAssessmentAgent;
use App\Contracts\SpeakingAssessmentServiceInterface;
use App\Contracts\TryoutAnswerServiceInterface;
use App\Contracts\TryoutAttemptServiceInterface;
use App\Contracts\TryoutMentorQuotaServiceInterface;
use App\Contracts\TryoutMentorServiceInterface;
use App\Contracts\WritingAssessmentServiceInterface;
use App\Enums\AiFeedbackStatus;
use App\Enums\TryoutMode;
use App\Livewire\Tryout\MentorChat;
use App\Livewire\Tryout\Sections\SpeakingSection;
use App\Models\Subscription;
use App\Models\Tryout;
use App\Models\TryoutAttempt;
use App\Models\TryoutAttemptSection;
use App\Models\TryoutQuestion;
use App\Models\User;
use App\Support\TryoutAiBudget;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\UseCheapestModel;
use Laravel\Ai\Attributes\UseSmartestModel;
use Laravel\Ai\Exceptions\RateLimitedException;
use Laravel\Ai\Transcription;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->tryout = Tryout::factory()->withIeltsSections(question_count: 2, units: 1, per_level: 2)->create();
    $this->attempts = app(TryoutAttemptServiceInterface::class);
    $this->answers = app(TryoutAnswerServiceInterface::class);
    $this->attempt = $this->attempts->start($this->user, $this->tryout, TryoutMode::Practice);
});

/**
 * Close sections until the given skill is open, and return its row.
 */
function openSkill(object $test, string $skill): TryoutAttemptSection
{
    while (($current = $test->attempts->currentSection($test->attempt->fresh())) !== null && $current->skill->value !== $skill) {
        $test->attempts->startSection($test->attempt->fresh());
        $test->attempts->submitSection($current->fresh());
    }

    $test->attempts->startSection($test->attempt->fresh());

    return $test->attempt->sections()->where('skill', $skill)->first();
}

/**
 * Agent fake that fails the first call and answers afterwards.
 */
function failOnce(mixed $answer): Closure
{
    $calls = 0;

    return function () use (&$calls, $answer) {
        if ($calls++ === 0) {
            throw RateLimitedException::forProvider('openrouter');
        }

        return $answer;
    };
}

/**
 * A valid structured writing assessment.
 *
 * @return array<string, mixed>
 */
function writingPayload(float $band = 6.0): array
{
    $row = ['band' => $band, 'reason' => 'Alasan singkat.'];

    return ['task' => $row, 'coherence' => $row, 'lexical' => $row, 'grammar' => $row, 'summary' => 'Ringkasan.'];
}

test('no try out agent pins its own provider or model', function (string $agent) {
    $reflection = new ReflectionClass($agent);

    foreach ([Model::class, Provider::class, UseCheapestModel::class, UseSmartestModel::class] as $attribute) {
        expect($reflection->getAttributes($attribute))->toBeEmpty();
    }
})->with([WritingAssessmentAgent::class, SpeakingAssessmentAgent::class, TryoutMentorAgent::class]);

test('every synchronous try out AI call fits the web request budget', function () {
    $budget = TryoutAiBudget::REQUEST_TIME_LIMIT_SECONDS - TryoutAiBudget::REQUEST_SAFETY_MARGIN_SECONDS;

    expect(WritingAssessmentAgent::TIMEOUT_SECONDS)->toBeLessThanOrEqual($budget)
        ->and(SpeakingAssessmentAgent::TIMEOUT_SECONDS)->toBeLessThanOrEqual($budget)
        ->and(TryoutAiBudget::LOCK_SECONDS)->toBeGreaterThanOrEqual(WritingAssessmentAgent::TIMEOUT_SECONDS);
});

test('a failed writing assessment keeps the essay, offers a retry, and the retry completes it', function () {
    $writing = openSkill($this, 'writing');
    $code = $this->answers->productivePrompts($writing)[0]->code;
    $this->answers->saveEssay($writing, $code, 'My essay text about seasons.');
    $this->attempts->submitSection($writing);

    WritingAssessmentAgent::fake(failOnce(writingPayload(6.5)));

    $answer = $writing->answers()->orderBy('sequence')->first();
    $failed = app(WritingAssessmentServiceInterface::class)->assess($answer);

    expect($failed->ai_status)->toBe(AiFeedbackStatus::Failed)
        ->and($failed->ai_error)->toContain('Jawabanmu tetap tersimpan')
        ->and($failed->response)->toBe('My essay text about seasons.')
        ->and($failed->canRetryAi())->toBeTrue();

    $done = app(WritingAssessmentServiceInterface::class)->retry($failed);

    expect($done->ai_status)->toBe(AiFeedbackStatus::Completed)
        ->and($done->band)->toBe(6.5);
});

test('a completed writing grade is never overwritten', function () {
    $writing = openSkill($this, 'writing');
    $code = $this->answers->productivePrompts($writing)[0]->code;
    $this->answers->saveEssay($writing, $code, 'Essay.');
    $this->attempts->submitSection($writing);

    WritingAssessmentAgent::fake([writingPayload(7.0)])->preventStrayPrompts();

    $answer = app(WritingAssessmentServiceInterface::class)->assess($writing->answers()->orderBy('sequence')->first());
    $again = app(WritingAssessmentServiceInterface::class)->retry($answer);

    expect($again->band)->toBe(7.0);
});

test('a spoken part stores only the browser transcript, then the subtest is graded', function () {
    Transcription::fake();
    SpeakingAssessmentAgent::fake([[
        'fluency' => ['band' => 6, 'reason' => 'a'], 'lexical' => ['band' => 6, 'reason' => 'b'],
        'grammar' => ['band' => 5.5, 'reason' => 'c'], 'pronunciation' => ['band' => 6, 'reason' => 'd'], 'summary' => 'ok',
    ]]);

    $speaking = openSkill($this, 'speaking');

    $component = Livewire::actingAs($this->user)
        ->test(SpeakingSection::class, ['attempt_section_id' => $speaking->id])
        ->call('submitPart', 0, "  I live in   Bandung and\nI like the food.  ", 42)
        ->assertHasNoErrors()
        ->assertSet('active', 1);

    expect($component->effects['returns'][0] ?? null)->toBeTrue();

    $answer = $speaking->answers()->orderBy('sequence')->first();

    expect($answer->transcript)->toBe('I live in Bandung and I like the food.')
        ->and($answer->word_count)->toBe(9)
        ->and($answer->time_spent_seconds)->toBe(42)
        ->and($answer->audio_path)->toBeNull()
        ->and($answer->answered_at)->not->toBeNull();

    // Transcription happens in the browser: the server never calls a speech model.
    Transcription::assertNothingGenerated();

    $this->attempts->submitSection($speaking->fresh());

    expect(app(SpeakingAssessmentServiceInterface::class)->nextStep($speaking->fresh()))->toBeTrue();

    $graded = $speaking->fresh();

    expect($graded->ai_status)->toBe(AiFeedbackStatus::Completed)
        ->and($graded->band)->toBe(6.0)
        ->and($graded->ai_assessment['pronunciation_is_estimate'])->toBeTrue();
});

test('a transcript longer than the limit is refused', function () {
    $speaking = openSkill($this, 'speaking');

    Livewire::actingAs($this->user)
        ->test(SpeakingSection::class, ['attempt_section_id' => $speaking->id])
        ->call('submitPart', 0, str_repeat('word ', 2000), 60)
        ->assertHasErrors('transcript');

    expect($speaking->answers()->whereNotNull('answered_at')->count())->toBe(0);
});

test('a speaking subtest without any answer scores 0 without calling the model', function () {
    SpeakingAssessmentAgent::fake()->preventStrayPrompts();

    $speaking = openSkill($this, 'speaking');
    $this->attempts->submitSection($speaking);

    app(SpeakingAssessmentServiceInterface::class)->nextStep($speaking->fresh());

    expect($speaking->fresh()->band)->toBe(0.0)
        ->and($speaking->fresh()->ai_status)->toBe(AiFeedbackStatus::Completed);
});

test('the mentor quota is reserved and given back when the model fails', function () {
    config(['pricing.enabled' => true, 'tryout.mentor.limit' => 2]);
    Subscription::query()->create(['user_id' => $this->user->id, 'plan' => 'pro_1bln', 'active_until' => now()->addMonth()]);
    $this->attempts->complete($this->attempt);

    TryoutMentorAgent::fake(failOnce('Jawaban mentor.'));

    $chat = Livewire::actingAs($this->user->fresh())->test(MentorChat::class, ['attempt_id' => $this->attempt->id]);
    $chat->set('question', 'Apa itu NOT GIVEN?')->call('send');

    $quota = app(TryoutMentorQuotaServiceInterface::class);
    expect($quota->remaining($this->user, $this->attempt))->toBe(2);

    $chat->set('question', 'Apa itu NOT GIVEN?')->call('send');
    expect($quota->remaining($this->user, $this->attempt))->toBe(1)
        ->and(collect($chat->get('messages'))->last()['content'])->toBe('Jawaban mentor.');
});

test('the mentor refuses a question that was not answered in the attempt', function () {
    config(['pricing.enabled' => true]);
    Subscription::query()->create(['user_id' => $this->user->id, 'plan' => 'pro_1bln', 'active_until' => now()->addMonth()]);
    TryoutMentorAgent::fake()->preventStrayPrompts();

    $other = TryoutQuestion::query()->first();

    expect(fn () => app(TryoutMentorServiceInterface::class)->ask($this->user->fresh(), $this->attempt, 'Kenapa?', $other->code))
        ->toThrow(ValidationException::class);
});

test('gratis cannot open the mentor chat', function () {
    config(['pricing.enabled' => true]);

    Livewire::actingAs($this->user)->test(MentorChat::class, ['attempt_id' => $this->attempt->id])->assertForbidden();
});

test('another mentee cannot use someone else\'s attempt in the mentor chat', function () {
    expect(TryoutAttempt::query()->count())->toBe(1);

    Livewire::actingAs(User::factory()->create())->test(MentorChat::class, ['attempt_id' => $this->attempt->id])->assertForbidden();
});
