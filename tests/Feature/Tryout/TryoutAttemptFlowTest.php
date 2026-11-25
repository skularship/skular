<?php

use App\Contracts\AdaptiveQuestionSelectorInterface;
use App\Contracts\TryoutAnswerServiceInterface;
use App\Contracts\TryoutAttemptServiceInterface;
use App\Enums\TryoutAttemptStatus;
use App\Enums\TryoutMode;
use App\Enums\TryoutSectionStatus;
use App\Enums\TryoutSkill;
use App\Exceptions\TryoutAnswerRejectedException;
use App\Exceptions\TryoutNotAvailableException;
use App\Exceptions\TryoutSectionExpiredException;
use App\Models\Subscription;
use App\Models\Tryout;
use App\Models\TryoutAnswer;
use App\Models\TryoutQuestion;
use App\Models\User;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->tryout = Tryout::factory()->withIeltsSections(question_count: 4, units: 2, per_level: 2)->create();
    $this->attempts = app(TryoutAttemptServiceInterface::class);
    $this->answers = app(TryoutAnswerServiceInterface::class);
});

/**
 * Answer the active question with its key (correct) or a wrong letter.
 */
function answerCurrent(object $test, bool $correct): mixed
{
    $section = $test->attempts->currentSection($test->attempt);
    $view = $test->answers->currentQuestion($section);
    $question = TryoutQuestion::query()->where('code', $view->code)->firstOrFail();

    return $test->answers->answerObjective($section, $view->code, $correct ? $question->answer_key : 'A');
}

test('the level goes up after a correct answer and down after a wrong one, within 1..5', function () {
    $selector = app(AdaptiveQuestionSelectorInterface::class);

    expect($selector->nextLevel(3, true))->toBe(4)
        ->and($selector->nextLevel(3, false))->toBe(2)
        ->and($selector->nextLevel(5, true))->toBe(5)
        ->and($selector->nextLevel(1, false))->toBe(1);
});

test('starting creates one row per subtest in L, R, W, S order and opens Listening', function () {
    $attempt = $this->attempts->start($this->user, $this->tryout, TryoutMode::Exam);

    expect($attempt->sections->pluck('skill')->all())->toBe([TryoutSkill::Listening, TryoutSkill::Reading, TryoutSkill::Writing, TryoutSkill::Speaking])
        ->and($attempt->current_skill)->toBe(TryoutSkill::Listening)
        ->and($attempt->sections->first()->status)->toBe(TryoutSectionStatus::InProgress)
        ->and($attempt->sections->first()->deadline_at)->not->toBeNull();
});

test('a second start returns the active attempt instead of creating another', function () {
    $first = $this->attempts->start($this->user, $this->tryout, TryoutMode::Practice);
    $second = $this->attempts->start($this->user, $this->tryout, TryoutMode::Practice);

    expect($second->id)->toBe($first->id)
        ->and($this->user->tryoutAttempts()->count())->toBe(1);
});

test('gratis gets one attempt per try out and pro may repeat', function () {
    config(['pricing.enabled' => true]);
    $attempt = $this->attempts->start($this->user, $this->tryout, TryoutMode::Practice);
    $this->attempts->complete($attempt);

    expect(fn () => $this->attempts->start($this->user, $this->tryout, TryoutMode::Practice))
        ->toThrow(TryoutNotAvailableException::class);

    Subscription::query()->create(['user_id' => $this->user->id, 'plan' => 'pro_1bln', 'active_until' => now()->addMonth()]);

    $again = $this->attempts->start($this->user->fresh(), $this->tryout, TryoutMode::Practice);

    expect($again->id)->not->toBe($attempt->id);
});

test('a draft try out cannot be started', function () {
    $draft = Tryout::factory()->draft()->withIeltsSections()->create();

    expect(fn () => $this->attempts->start($this->user, $draft, TryoutMode::Practice))
        ->toThrow(TryoutNotAvailableException::class);
});

test('a refresh shows the same served question', function () {
    $this->attempt = $this->attempts->start($this->user, $this->tryout, TryoutMode::Practice);
    $section = $this->attempts->currentSection($this->attempt);

    $first = $this->answers->currentQuestion($section);
    $again = $this->answers->currentQuestion($section->fresh());

    expect($again->code)->toBe($first->code)
        ->and(TryoutAnswer::query()->count())->toBe(1)
        ->and($first->sequence)->toBe(1);
});

test('the first question is on the start level and the next follows the answer', function () {
    $this->attempt = $this->attempts->start($this->user, $this->tryout, TryoutMode::Practice);
    $section = $this->attempts->currentSection($this->attempt);
    $first = $this->answers->currentQuestion($section);

    expect(TryoutQuestion::query()->where('code', $first->code)->value('level'))->toBe(3);

    $result = answerCurrent($this, true);

    expect(TryoutQuestion::query()->where('code', $result->next_question->code)->value('level'))->toBe(4)
        ->and($section->fresh()->current_level)->toBe(4);

    $result = answerCurrent($this, false);

    expect(TryoutQuestion::query()->where('code', $result->next_question->code)->value('level'))->toBe(3);
});

test('the result of an answer never says whether it was correct', function () {
    $this->attempt = $this->attempts->start($this->user, $this->tryout, TryoutMode::Practice);

    $result = answerCurrent($this, true);

    expect(array_keys($result->toArray()))->not->toContain('is_correct');
});

test('an answer for another question than the active one is rejected (second tab)', function () {
    $this->attempt = $this->attempts->start($this->user, $this->tryout, TryoutMode::Practice);
    $section = $this->attempts->currentSection($this->attempt);
    $view = $this->answers->currentQuestion($section);

    answerCurrent($this, true);

    expect(fn () => $this->answers->answerObjective($section->fresh(), $view->code, 'B'))
        ->toThrow(TryoutAnswerRejectedException::class);
});

test('a block switches to a new unit after questions_per_unit questions', function () {
    $this->attempt = $this->attempts->start($this->user, $this->tryout, TryoutMode::Practice);

    $units = [];

    for ($i = 0; $i < 4; $i++) {
        $section = $this->attempts->currentSection($this->attempt);
        $view = $this->answers->currentQuestion($section);
        $units[] = $view->unit->code;
        answerCurrent($this, $i % 2 === 0);
    }

    expect($units[0])->toBe($units[1])
        ->and($units[2])->toBe($units[3])
        ->and($units[0])->not->toBe($units[2]);
});

test('answering every question scores listening and moves the attempt to reading', function () {
    $this->attempt = $this->attempts->start($this->user, $this->tryout, TryoutMode::Practice);

    foreach ([true, true, false, true] as $correct) {
        $result = answerCurrent($this, $correct);
    }

    $listening = $this->attempt->sections()->where('skill', 'listening')->first();

    expect($result->section_finished)->toBeTrue()
        ->and($listening->status)->toBe(TryoutSectionStatus::Scored)
        ->and($listening->raw_score)->toBe(3)
        ->and($listening->max_score)->toBe(4)
        ->and($listening->band)->not->toBeNull()
        ->and($this->attempt->fresh()->current_skill)->toBe(TryoutSkill::Reading);

    // Reading only starts when the mentee presses "Lanjut".
    expect($this->attempt->sections()->where('skill', 'reading')->first()->status)->toBe(TryoutSectionStatus::Pending);
});

test('answers after the deadline are refused and the subtest is closed', function () {
    $this->attempt = $this->attempts->start($this->user, $this->tryout, TryoutMode::Practice);
    $section = $this->attempts->currentSection($this->attempt);
    $view = $this->answers->currentQuestion($section);

    Carbon::setTestNow(now()->addSeconds($section->section->totalSeconds() + 10));

    expect(fn () => $this->answers->answerObjective($section, $view->code, 'B'))
        ->toThrow(TryoutSectionExpiredException::class);

    expect($section->fresh()->status)->toBe(TryoutSectionStatus::Scored)
        ->and($section->fresh()->raw_score)->toBe(0)
        // The unanswered served question counts as 0 and has no row.
        ->and($section->answers()->count())->toBe(0);

    Carbon::setTestNow();
});

test('writing serves one prompt per task and autosaves the essay', function () {
    $this->attempt = $this->attempts->start($this->user, $this->tryout, TryoutMode::Practice);

    foreach (['listening', 'reading'] as $skill) {
        $this->attempts->submitSection($this->attempt->sections()->where('skill', $skill)->first());
        $this->attempts->startSection($this->attempt->fresh());
    }

    $writing = $this->attempts->currentSection($this->attempt->fresh());
    $prompts = $this->answers->productivePrompts($writing);

    expect($writing->skill)->toBe(TryoutSkill::Writing)
        ->and($prompts)->toHaveCount(2)
        ->and($prompts[0]->part)->toBe(1);

    $saved = $this->answers->saveEssay($writing, $prompts[0]->code, 'The table compares four seasons.');

    expect($saved->word_count)->toBe(5)
        ->and($saved->fresh()->response)->toBe('The table compares four seasons.');
});

test('completing every subtest completes the attempt', function () {
    $this->attempt = $this->attempts->start($this->user, $this->tryout, TryoutMode::Practice);

    foreach (['listening', 'reading', 'writing', 'speaking'] as $skill) {
        $this->attempts->startSection($this->attempt->fresh());
        $this->attempts->submitSection($this->attempt->sections()->where('skill', $skill)->first());
    }

    expect($this->attempt->fresh()->status)->toBe(TryoutAttemptStatus::Completed);
});
