<?php

use App\Contracts\IqAttemptServiceInterface;
use App\Contracts\IqScoringServiceInterface;
use App\Enums\IqAttemptStatus;
use App\Enums\IqSubtest;
use App\Exceptions\IqAnswerRejectedException;
use App\Exceptions\IqNotAvailableException;
use App\Models\IqAnswer;
use App\Models\IqAttempt;
use App\Models\IqQuestion;
use App\Models\IqTest;
use App\Models\User;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->test = IqTest::factory()->withQuestions()->create();
    $this->attempts = app(IqAttemptServiceInterface::class);
});

afterEach(function () {
    Carbon::setTestNow();
});

test('starting freezes the 35 active questions with a server deadline of 20 minutes', function () {
    Carbon::setTestNow('2026-11-25 10:00:00');
    IqQuestion::factory()->for($this->test, 'test')->inactive()->create(['number' => 99]);

    $attempt = $this->attempts->start($this->user, $this->test);

    expect($attempt->status)->toBe(IqAttemptStatus::InProgress)
        ->and($attempt->total_questions)->toBe(35)
        ->and($attempt->question_ids)->toHaveCount(35)
        ->and($attempt->deadline_at->toDateTimeString())->toBe('2026-11-25 10:20:00')
        ->and($attempt->answers()->count())->toBe(35);
});

test('one account has at most one active attempt', function () {
    $first = $this->attempts->start($this->user, $this->test);
    $second = $this->attempts->start($this->user, $this->test);

    expect($second->id)->toBe($first->id)
        ->and($this->user->iqAttempts()->count())->toBe(1);
});

test('a draft test or a test without active questions cannot be started', function () {
    $draft = IqTest::factory()->draft()->withQuestions()->create();
    $empty = IqTest::factory()->create();

    expect(fn () => $this->attempts->start($this->user, $draft))->toThrow(IqNotAvailableException::class)
        ->and(fn () => $this->attempts->start($this->user, $empty))->toThrow(IqNotAvailableException::class);
});

test('a pick is saved at once and can be changed before sending', function () {
    $attempt = $this->attempts->start($this->user, $this->test);

    $this->attempts->answer($attempt, 1, 'b');
    $result = $this->attempts->answer($attempt, 1, 'A');

    $answer = IqAnswer::query()->where('iq_attempt_id', $attempt->id)->where('iq_question_id', $attempt->question_ids[0])->first();

    expect($answer->choice)->toBe('A')
        ->and($answer->changes)->toBe(1)
        ->and($answer->is_correct)->toBeNull()
        ->and($result->answered_count)->toBe(1)
        ->and($this->attempts->answeredPositions($attempt))->toBe([1]);
});

test('invalid letters and positions are refused', function () {
    $attempt = $this->attempts->start($this->user, $this->test);

    expect(fn () => $this->attempts->answer($attempt, 1, 'E'))->toThrow(IqAnswerRejectedException::class)
        ->and(fn () => $this->attempts->answer($attempt, 36, 'A'))->toThrow(IqAnswerRejectedException::class);
});

test('an answer after the deadline is refused and closes the attempt as expired', function () {
    Carbon::setTestNow('2026-11-25 10:00:00');
    $attempt = $this->attempts->start($this->user, $this->test);
    $this->attempts->answer($attempt, 1, 'A');

    Carbon::setTestNow('2026-11-25 10:20:30');

    expect(fn () => $this->attempts->answer($attempt, 2, 'A'))->toThrow(IqAnswerRejectedException::class);

    $attempt->refresh();

    expect($attempt->status)->toBe(IqAttemptStatus::Expired)
        ->and($attempt->answered_count)->toBe(1)
        ->and($attempt->correct_count)->toBe(1)
        ->and($attempt->completed_at->toDateTimeString())->toBe('2026-11-25 10:20:00')
        ->and(IqAnswer::query()->where('iq_attempt_id', $attempt->id)->whereNotNull('choice')->count())->toBe(1);
});

test('a closed attempt refuses changes', function () {
    $attempt = $this->attempts->start($this->user, $this->test);
    $this->attempts->finish($attempt);

    expect($attempt->status)->toBe(IqAttemptStatus::Completed)
        ->and(fn () => $this->attempts->answer($attempt, 1, 'A'))->toThrow(IqAnswerRejectedException::class);
});

test('scores per subtest and the category are computed on the server when sent', function () {
    $attempt = $this->attempts->start($this->user, $this->test);

    // Numerik 1-7: 5 correct, 1 wrong, 1 empty. Logika 20-27: all correct. Rest unanswered.
    foreach ([1, 2, 3, 4, 5] as $position) {
        $this->attempts->answer($attempt, $position, 'A');
    }

    $this->attempts->answer($attempt, 6, 'B');

    foreach (range(20, 27) as $position) {
        $this->attempts->answer($attempt, $position, 'A');
    }

    $this->attempts->finish($attempt);
    $attempt->refresh();

    expect($attempt->status)->toBe(IqAttemptStatus::Completed)
        ->and($attempt->answered_count)->toBe(14)
        ->and($attempt->correct_count)->toBe(13)
        ->and($attempt->score_percent)->toBe(37)
        ->and($attempt->category)->toBe('Perlu latihan dasar')
        ->and($attempt->subtest_scores['numerik'])->toBe(['correct' => 5, 'total' => 7])
        ->and($attempt->subtest_scores['logika'])->toBe(['correct' => 8, 'total' => 8])
        ->and(array_keys($attempt->subtest_scores))->toBe(['numerik', 'visuospasial', 'verbal', 'logika', 'teks']);
});

test('the category follows the 80/60/40 thresholds', function () {
    $scoring = app(IqScoringServiceInterface::class);

    expect($scoring->category(100))->toBe('Sangat baik')
        ->and($scoring->category(80))->toBe('Sangat baik')
        ->and($scoring->category(79))->toBe('Baik')
        ->and($scoring->category(60))->toBe('Baik')
        ->and($scoring->category(59))->toBe('Cukup, perlu latihan')
        ->and($scoring->category(40))->toBe('Cukup, perlu latihan')
        ->and($scoring->category(39))->toBe('Perlu latihan dasar')
        ->and($scoring->category(0))->toBe('Perlu latihan dasar');
});

test('a key fixed by the admin before sending is used for the score', function () {
    $attempt = $this->attempts->start($this->user, $this->test);
    $this->attempts->answer($attempt, 1, 'C');

    IqQuestion::query()->whereKey($attempt->question_ids[0])->update(['answer_key' => 'C']);
    $this->attempts->finish($attempt);

    expect($attempt->fresh()->correct_count)->toBe(1);
});

test('support messages appear at 25, 50 and 75 percent and only use the mentee counts', function () {
    $attempt = $this->attempts->start($this->user, $this->test);
    $messages = [];

    foreach (range(1, 35) as $position) {
        $result = $this->attempts->answer($attempt, $position, $position % 2 === 0 ? 'B' : 'A');

        if ($result->support_message !== null) {
            $messages[$position] = $result->support_message;
        }
    }

    expect(array_keys($messages))->toBe([9, 18, 27])
        ->and($messages[18])->toBe('Kamu sudah menjawab 18 dari 35 soal. Di bagian Verbal, kamu sudah menjawab 4 dari 5 soal.')
        ->and($messages[27])->toContain('Di bagian Logika, kamu sudah menjawab 8 dari 8 soal.');

    foreach ($messages as $message) {
        expect($message)->not->toContain('benar')
            ->not->toContain('hebat')
            ->not->toContain('peserta');
    }
});

test('changing an answer never repeats a support message', function () {
    $attempt = $this->attempts->start($this->user, $this->test);

    foreach (range(1, 9) as $position) {
        $this->attempts->answer($attempt, $position, 'A');
    }

    expect($this->attempts->answer($attempt, 9, 'B')->support_message)->toBeNull();
});

test('time per question is measured on the server between visits', function () {
    Carbon::setTestNow('2026-11-25 10:00:00');
    $attempt = $this->attempts->start($this->user, $this->test);

    $this->attempts->question($attempt, 1);
    Carbon::setTestNow('2026-11-25 10:00:42');
    $this->attempts->question($attempt->fresh(), 2);
    Carbon::setTestNow('2026-11-25 10:01:00');
    $this->attempts->finish($attempt->fresh());

    $times = IqAnswer::query()->where('iq_attempt_id', $attempt->id)->pluck('time_spent_seconds', 'iq_question_id');

    expect($times[$attempt->question_ids[0]])->toBe(42)
        ->and($times[$attempt->question_ids[1]])->toBe(18);
});

test('the question view never carries the key or the explanation', function () {
    $attempt = $this->attempts->start($this->user, $this->test);
    $view = $this->attempts->question($attempt, 8)->toArray();

    expect($view)->not->toHaveKey('answer_key')
        ->not->toHaveKey('explanation')
        ->and($view['subtest'])->toBe(IqSubtest::Visuospasial->value)
        ->and($view['image_alt'])->not->toBeEmpty()
        ->and($view['options'])->toHaveKeys(['A', 'B', 'C', 'D']);
});

test('the scheduled command closes attempts whose time ran out', function () {
    Carbon::setTestNow('2026-11-25 10:00:00');
    $attempt = $this->attempts->start($this->user, $this->test);

    Carbon::setTestNow('2026-11-25 10:30:00');
    $this->artisan('iq:close-expired')->assertSuccessful();

    expect($attempt->fresh()->status)->toBe(IqAttemptStatus::Expired);
});

test('an expired active attempt does not block a new one', function () {
    Carbon::setTestNow('2026-11-25 10:00:00');
    $first = $this->attempts->start($this->user, $this->test);

    Carbon::setTestNow('2026-11-25 11:00:00');
    $second = $this->attempts->start($this->user, $this->test);

    expect($second->id)->not->toBe($first->id)
        ->and($first->fresh()->status)->toBe(IqAttemptStatus::Expired)
        ->and(IqAttempt::query()->where('status', IqAttemptStatus::InProgress->value)->count())->toBe(1);
});

test('a question mentees saw cannot be deleted', function () {
    $attempt = $this->attempts->start($this->user, $this->test);
    $question = IqQuestion::query()->findOrFail($attempt->question_ids[0]);

    expect($question->delete())->toBeFalse()
        ->and(IqQuestion::query()->whereKey($question->id)->exists())->toBeTrue();
});
