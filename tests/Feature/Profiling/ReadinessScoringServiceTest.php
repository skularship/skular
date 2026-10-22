<?php

use App\Contracts\ReadinessScoringServiceInterface;
use App\Data\ReadinessResultData;
use App\Enums\QuickProfilingAsset;
use App\Enums\QuickProfilingGoal;
use App\Enums\QuickProfilingStatus;
use App\Enums\QuickProfilingTimeline;
use App\Enums\QuickProfilingWeeklyTime;
use App\Enums\ReadinessCategory;

/**
 * Score the quick assessment through the resolved service.
 *
 * @param  array<int, QuickProfilingAsset>  $assets
 */
function scoreReadiness(
    QuickProfilingStatus $status,
    QuickProfilingGoal $goal,
    QuickProfilingTimeline $timeline,
    array $assets,
    QuickProfilingWeeklyTime $weekly_time,
): ReadinessResultData {
    return app(ReadinessScoringServiceInterface::class)
        ->score($status, $goal, $timeline, $assets, $weekly_time);
}

test('the readiness score always stays within zero and one hundred', function () {
    $service = app(ReadinessScoringServiceInterface::class);

    foreach (QuickProfilingStatus::cases() as $status) {
        foreach (QuickProfilingGoal::cases() as $goal) {
            foreach (QuickProfilingTimeline::cases() as $timeline) {
                foreach (QuickProfilingWeeklyTime::cases() as $weekly_time) {
                    foreach ([[QuickProfilingAsset::None], QuickProfilingAsset::cases()] as $assets) {
                        $score = $service->score($status, $goal, $timeline, $assets, $weekly_time)->score;

                        expect($score)->toBeGreaterThanOrEqual(0)->toBeLessThanOrEqual(100);
                    }
                }
            }
        }
    }
});

test('identical answers always produce the identical result', function () {
    $first = scoreReadiness(
        QuickProfilingStatus::S1,
        QuickProfilingGoal::S2DalamNegeri,
        QuickProfilingTimeline::ThreeToSixMonths,
        [QuickProfilingAsset::EssayDraft, QuickProfilingAsset::Organization],
        QuickProfilingWeeklyTime::TwoToFiveHours,
    );

    $second = scoreReadiness(
        QuickProfilingStatus::S1,
        QuickProfilingGoal::S2DalamNegeri,
        QuickProfilingTimeline::ThreeToSixMonths,
        [QuickProfilingAsset::EssayDraft, QuickProfilingAsset::Organization],
        QuickProfilingWeeklyTime::TwoToFiveHours,
    );

    expect($first->toArray())->toBe($second->toArray())
        ->and($first->score)->toBe(61)
        ->and($first->category)->toBe(ReadinessCategory::BuildingReadiness);
});

test('a fully prepared mentee reaches the top band', function () {
    $result = scoreReadiness(
        QuickProfilingStatus::SMA,
        QuickProfilingGoal::BeasiswaS1,
        QuickProfilingTimeline::OverTwelveMonths,
        [
            QuickProfilingAsset::LanguageScore,
            QuickProfilingAsset::EssayDraft,
            QuickProfilingAsset::Achievement,
            QuickProfilingAsset::Organization,
        ],
        QuickProfilingWeeklyTime::OverFiveHours,
    );

    expect($result->score)->toBe(100)
        ->and($result->category)->toBe(ReadinessCategory::StrongFoundation)
        ->and($result->headline)->toBe('Kamu Punya Modal, Sekarang Susun Strateginya');
});

test('an empty portfolio lands in the lowest band', function () {
    $result = scoreReadiness(
        QuickProfilingStatus::SMA,
        QuickProfilingGoal::S2LuarNegeri,
        QuickProfilingTimeline::UnderThreeMonths,
        [QuickProfilingAsset::None],
        QuickProfilingWeeklyTime::UnderTwoHours,
    );

    expect($result->score)->toBe(18)
        ->and($result->category)->toBe(ReadinessCategory::NeedsPreparation)
        ->and($result->blocker)->toBe('Belum memiliki modal persiapan yang diperlukan.')
        ->and($result->recommendations)->toHaveCount(3);
});

test('the "Belum Ada" answer is ignored when a real asset is present', function () {
    $withNone = scoreReadiness(
        QuickProfilingStatus::S1,
        QuickProfilingGoal::S2DalamNegeri,
        QuickProfilingTimeline::SixToTwelveMonths,
        [QuickProfilingAsset::None, QuickProfilingAsset::EssayDraft],
        QuickProfilingWeeklyTime::TwoToFiveHours,
    );

    $withoutNone = scoreReadiness(
        QuickProfilingStatus::S1,
        QuickProfilingGoal::S2DalamNegeri,
        QuickProfilingTimeline::SixToTwelveMonths,
        [QuickProfilingAsset::EssayDraft],
        QuickProfilingWeeklyTime::TwoToFiveHours,
    );

    expect($withNone->score)->toBe($withoutNone->score);
});

test('the blocker names the weakest of the three scored dimensions', function () {
    $limited_time = scoreReadiness(
        QuickProfilingStatus::S1,
        QuickProfilingGoal::S2DalamNegeri,
        QuickProfilingTimeline::OverTwelveMonths,
        QuickProfilingAsset::cases(),
        QuickProfilingWeeklyTime::UnderTwoHours,
    );

    $tight_deadline = scoreReadiness(
        QuickProfilingStatus::S1,
        QuickProfilingGoal::S2DalamNegeri,
        QuickProfilingTimeline::UnderThreeMonths,
        QuickProfilingAsset::cases(),
        QuickProfilingWeeklyTime::OverFiveHours,
    );

    expect($limited_time->blocker)->toBe('Waktu persiapan mingguan masih terbatas.')
        ->and($tight_deadline->blocker)->toBe('Target pendaftaran sudah dekat sehingga perlu prioritas yang jelas.');
});

test('the recommendations follow the answers instead of a fixed template', function () {
    $language_gap = scoreReadiness(
        QuickProfilingStatus::S1,
        QuickProfilingGoal::S2LuarNegeri,
        QuickProfilingTimeline::OverTwelveMonths,
        [QuickProfilingAsset::EssayDraft],
        QuickProfilingWeeklyTime::OverFiveHours,
    );

    $complete_portfolio = scoreReadiness(
        QuickProfilingStatus::S1,
        QuickProfilingGoal::S2LuarNegeri,
        QuickProfilingTimeline::OverTwelveMonths,
        QuickProfilingAsset::cases(),
        QuickProfilingWeeklyTime::OverFiveHours,
    );

    expect($language_gap->recommendations[0])->toContain('tes kemampuan bahasa')
        ->and($complete_portfolio->recommendations[0])->toContain('Modal persiapanmu sudah lengkap')
        ->and($language_gap->recommendations)->not->toBe($complete_portfolio->recommendations);
});

test('changing an answer changes the result', function () {
    $short = scoreReadiness(
        QuickProfilingStatus::S1,
        QuickProfilingGoal::S2DalamNegeri,
        QuickProfilingTimeline::UnderThreeMonths,
        [QuickProfilingAsset::EssayDraft],
        QuickProfilingWeeklyTime::UnderTwoHours,
    );

    $long = scoreReadiness(
        QuickProfilingStatus::S1,
        QuickProfilingGoal::S2DalamNegeri,
        QuickProfilingTimeline::OverTwelveMonths,
        [QuickProfilingAsset::EssayDraft, QuickProfilingAsset::LanguageScore, QuickProfilingAsset::Achievement],
        QuickProfilingWeeklyTime::OverFiveHours,
    );

    expect($long->score)->toBeGreaterThan($short->score)
        ->and($long->category)->not->toBe($short->category)
        ->and($long->headline)->not->toBe($short->headline);
});

test('the category band matches the score range', function () {
    expect(ReadinessCategory::forScore(0))->toBe(ReadinessCategory::NeedsPreparation)
        ->and(ReadinessCategory::forScore(39))->toBe(ReadinessCategory::NeedsPreparation)
        ->and(ReadinessCategory::forScore(40))->toBe(ReadinessCategory::BuildingReadiness)
        ->and(ReadinessCategory::forScore(69))->toBe(ReadinessCategory::BuildingReadiness)
        ->and(ReadinessCategory::forScore(70))->toBe(ReadinessCategory::StrongFoundation)
        ->and(ReadinessCategory::forScore(100))->toBe(ReadinessCategory::StrongFoundation);
});

test('the timeline summary never invents a specific deadline', function () {
    $result = scoreReadiness(
        QuickProfilingStatus::S1,
        QuickProfilingGoal::S2DalamNegeri,
        QuickProfilingTimeline::ThreeToSixMonths,
        [QuickProfilingAsset::EssayDraft],
        QuickProfilingWeeklyTime::TwoToFiveHours,
    );

    expect($result->timeline_summary)->not->toMatch('/\d{4}-\d{2}-\d{2}|\d+\s+(hari|bulan|tanggal)\s+\d/');
});
