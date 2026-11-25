<?php

use App\Contracts\AnswerCheckerInterface;
use App\Contracts\TryoutScoringServiceInterface;
use App\Enums\TryoutSkill;
use App\Models\TryoutQuestion;

test('band rounding snaps quarters up to the next half band', function (float $value, float $expected) {
    expect(app(TryoutScoringServiceInterface::class)->roundBand($value))->toBe($expected);
})->with([
    [6.25, 6.5],
    [6.75, 7.0],
    [6.1, 6.0],
    [6.2499999, 6.5],
    [6.6, 6.5],
    [9.4, 9.0],
]);

test('writing band counts task 2 twice', function () {
    // (6.5 + 2 x 6.0) / 3 = 6.17 -> 6.0
    expect(app(TryoutScoringServiceInterface::class)->writingBand(6.5, 6.0))->toBe(6.0)
        ->and(app(TryoutScoringServiceInterface::class)->writingBand(7.0, 6.5))->toBe(6.5);
});

test('raw scores map to bands from the config table and scale other question counts', function () {
    $scoring = app(TryoutScoringServiceInterface::class);

    expect($scoring->bandFromRaw(TryoutSkill::Listening, 40))->toBe(9.0)
        ->and($scoring->bandFromRaw(TryoutSkill::Listening, 30))->toBe(7.0)
        ->and($scoring->bandFromRaw(TryoutSkill::Reading, 0))->toBe(0.0)
        // 2 of 4 = 20 of 40.
        ->and($scoring->bandFromRaw(TryoutSkill::Reading, 2, 4))->toBe(5.5);
});

test('the answer checker accepts keys, alternatives, case, trailing period and thousands separators', function () {
    $checker = app(AnswerCheckerInterface::class);
    $question = TryoutQuestion::factory()->shortText('2600', ['two thousand six hundred'])->make();

    expect($checker->isCorrect($question, '2,600'))->toBeTrue()
        ->and($checker->isCorrect($question, ' Two  Thousand six hundred. '))->toBeTrue()
        ->and($checker->isCorrect($question, '2700'))->toBeFalse()
        ->and($checker->isCorrect($question, ''))->toBeFalse();

    $choice = TryoutQuestion::factory()->make(['answer_key' => 'B']);

    expect($checker->isCorrect($choice, 'b'))->toBeTrue()
        ->and($checker->isCorrect($choice, 'C'))->toBeFalse();
});

test('a strict word limit marks a long short-text answer wrong', function () {
    $question = TryoutQuestion::factory()->shortText('library', [], 1)->make();

    config(['tryout.strict_word_limit' => false]);
    expect(app(AnswerCheckerInterface::class)->isCorrect($question, 'library'))->toBeTrue();

    config(['tryout.strict_word_limit' => true]);
    expect(app(AnswerCheckerInterface::class)->isCorrect($question, 'the library'))->toBeFalse();
});
