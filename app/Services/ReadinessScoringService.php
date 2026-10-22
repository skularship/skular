<?php

namespace App\Services;

use App\Contracts\ReadinessScoringServiceInterface;
use App\Data\ReadinessResultData;
use App\Enums\QuickProfilingAsset;
use App\Enums\QuickProfilingGoal;
use App\Enums\QuickProfilingStatus;
use App\Enums\QuickProfilingTimeline;
use App\Enums\QuickProfilingWeeklyTime;
use App\Enums\ReadinessCategory;

/**
 * Scores the five Profiling Kilat answers into a 0-100 readiness result.
 *
 * The score is a preparation signal, never a probability of winning a
 * scholarship. Status and goal only nudge the score as context; they never
 * decide eligibility.
 */
class ReadinessScoringService implements ReadinessScoringServiceInterface
{
    public const WEIGHT_ASSETS = 40;

    public const WEIGHT_WEEKLY_TIME = 25;

    public const WEIGHT_TIMELINE = 25;

    public const WEIGHT_CONTEXT = 10;

    /**
     * Assets named in this order when a recommendation has to point at a gap.
     *
     * @var array<int, QuickProfilingAsset>
     */
    private const ASSET_PRIORITY = [
        QuickProfilingAsset::LanguageScore,
        QuickProfilingAsset::EssayDraft,
        QuickProfilingAsset::Achievement,
        QuickProfilingAsset::Organization,
    ];

    public function score(
        QuickProfilingStatus $status,
        QuickProfilingGoal $goal,
        QuickProfilingTimeline $timeline,
        array $assets,
        QuickProfilingWeeklyTime $weekly_time,
    ): ReadinessResultData {
        $assets = $this->normaliseAssets($assets);

        $asset_points = min(self::WEIGHT_ASSETS, $this->assetPoints($assets));
        $weekly_points = $weekly_time->points();
        $timeline_points = $timeline->points();
        $context_points = $this->contextPoints($status, $goal);

        $score = max(0, min(100, $asset_points + $weekly_points + $timeline_points + $context_points));
        $category = ReadinessCategory::forScore($score);

        return new ReadinessResultData(
            score: $score,
            category: $category,
            headline: $category->headline(),
            subheadline: $category->subheadline(),
            blocker: $this->blocker($asset_points, $weekly_points, $timeline_points),
            recommendations: $this->recommendations($goal, $timeline, $weekly_time, $assets, $asset_points, $weekly_points, $timeline_points),
            timeline_summary: $this->timelineSummary($timeline),
        );
    }

    /**
     * Keep known assets, drop duplicates and honour the exclusive "Belum Ada".
     *
     * @param  array<int, QuickProfilingAsset>  $assets
     * @return array<int, QuickProfilingAsset>
     */
    private function normaliseAssets(array $assets): array
    {
        $unique = [];

        foreach ($assets as $asset) {
            $unique[$asset->value] = $asset;
        }

        $values = array_values($unique);

        if (count($values) > 1) {
            $values = array_values(array_filter(
                $values,
                fn (QuickProfilingAsset $asset): bool => ! $asset->isExclusive(),
            ));
        }

        return $values;
    }

    /**
     * Sum the points of the assets the mentee already owns.
     *
     * @param  array<int, QuickProfilingAsset>  $assets
     */
    private function assetPoints(array $assets): int
    {
        return (int) array_sum(array_map(
            fn (QuickProfilingAsset $asset): int => $asset->points(),
            $assets,
        ));
    }

    /**
     * Score how naturally the goal follows from the current status.
     *
     * This is context only: it never blocks a combination, it just nudges the
     * total by at most ten points.
     */
    private function contextPoints(QuickProfilingStatus $status, QuickProfilingGoal $goal): int
    {
        return match ($goal) {
            QuickProfilingGoal::BeasiswaS1 => match ($status) {
                QuickProfilingStatus::SMA => 10,
                QuickProfilingStatus::S1 => 5,
                QuickProfilingStatus::FreshGraduate => 4,
                QuickProfilingStatus::Worker => 3,
            },
            QuickProfilingGoal::S2DalamNegeri => match ($status) {
                QuickProfilingStatus::SMA => 3,
                QuickProfilingStatus::S1 => 10,
                QuickProfilingStatus::FreshGraduate => 10,
                QuickProfilingStatus::Worker => 8,
            },
            QuickProfilingGoal::S2LuarNegeri => match ($status) {
                QuickProfilingStatus::SMA => 2,
                QuickProfilingStatus::S1 => 9,
                QuickProfilingStatus::FreshGraduate => 9,
                QuickProfilingStatus::Worker => 7,
            },
            QuickProfilingGoal::Lomba => match ($status) {
                QuickProfilingStatus::SMA => 8,
                QuickProfilingStatus::S1 => 9,
                QuickProfilingStatus::FreshGraduate => 7,
                QuickProfilingStatus::Worker => 5,
            },
        };
    }

    /**
     * Name the dimension that holds the mentee back the most.
     */
    private function blocker(int $asset_points, int $weekly_points, int $timeline_points): string
    {
        $ratios = $this->dimensionRatios($asset_points, $weekly_points, $timeline_points);
        $minimum = min($ratios);

        if ($ratios['assets'] <= $minimum) {
            return 'Belum memiliki modal persiapan yang diperlukan.';
        }

        if ($ratios['weekly'] <= $minimum) {
            return 'Waktu persiapan mingguan masih terbatas.';
        }

        return 'Target pendaftaran sudah dekat sehingga perlu prioritas yang jelas.';
    }

    /**
     * Build exactly three actions, most urgent dimension first.
     *
     * @param  array<int, QuickProfilingAsset>  $assets
     * @return array<int, string>
     */
    private function recommendations(
        QuickProfilingGoal $goal,
        QuickProfilingTimeline $timeline,
        QuickProfilingWeeklyTime $weekly_time,
        array $assets,
        int $asset_points,
        int $weekly_points,
        int $timeline_points,
    ): array {
        $ratios = $this->dimensionRatios($asset_points, $weekly_points, $timeline_points);
        asort($ratios);

        $recommendations = [];

        foreach (array_keys($ratios) as $dimension) {
            $recommendations[] = match ($dimension) {
                'weekly' => $this->weeklyRecommendation($weekly_time),
                'timeline' => $this->timelineRecommendation($timeline),
                default => $this->assetRecommendation($goal, $assets),
            };
        }

        return $recommendations;
    }

    /**
     * Get the fill ratio of every scored dimension.
     *
     * @return array{assets: float, weekly: float, timeline: float}
     */
    private function dimensionRatios(int $asset_points, int $weekly_points, int $timeline_points): array
    {
        return [
            'assets' => $asset_points / self::WEIGHT_ASSETS,
            'weekly' => $weekly_points / self::WEIGHT_WEEKLY_TIME,
            'timeline' => $timeline_points / self::WEIGHT_TIMELINE,
        ];
    }

    /**
     * Recommend the next concrete step for the preparation assets.
     *
     * @param  array<int, QuickProfilingAsset>  $assets
     */
    private function assetRecommendation(QuickProfilingGoal $goal, array $assets): string
    {
        $owned = array_map(fn (QuickProfilingAsset $asset): string => $asset->value, $assets);

        $missing = array_values(array_filter(
            self::ASSET_PRIORITY,
            fn (QuickProfilingAsset $asset): bool => ! in_array($asset->value, $owned, true),
        ));

        if ($missing === []) {
            return 'Modal persiapanmu sudah lengkap; tinjau ulang tiap bukti agar relevan dengan persyaratan beasiswa yang kamu tuju.';
        }

        $messages = [
            QuickProfilingAsset::LanguageScore->value => $goal === QuickProfilingGoal::S2LuarNegeri
                ? 'Jadwalkan tes kemampuan bahasa (IELTS/TOEFL) karena targetmu berkuliah di luar negeri.'
                : 'Jadwalkan tes kemampuan bahasa (IELTS/TOEFL atau tes internal kampus) untuk melengkapi modalmu.',
            QuickProfilingAsset::EssayDraft->value => 'Tulis draf esai motivasi pertamamu, lalu minta satu orang terpercaya memberi masukan.',
            QuickProfilingAsset::Achievement->value => 'Ikuti satu kompetisi atau proyek di bidang minatmu untuk menambah bukti prestasi.',
            QuickProfilingAsset::Organization->value => 'Ambil satu peran di organisasi, kepanitiaan, atau kegiatan sosial yang bisa kamu ceritakan saat mendaftar.',
        ];

        return $messages[$missing[0]->value];
    }

    /**
     * Recommend how to turn the weekly allocation into a routine.
     */
    private function weeklyRecommendation(QuickProfilingWeeklyTime $weekly_time): string
    {
        return match ($weekly_time) {
            QuickProfilingWeeklyTime::UnderTwoHours => 'Sisihkan minimal 2 jam per minggu untuk persiapan beasiswa dan blok waktunya di kalender agar tidak kalah oleh rutinitas.',
            QuickProfilingWeeklyTime::TwoToFiveHours => 'Ubah 2–5 jam mingguanmu menjadi jadwal tetap, misalnya dua sesi 90 menit untuk dokumen dan esai.',
            QuickProfilingWeeklyTime::OverFiveHours => 'Manfaatkan alokasi lebih dari 5 jam per minggu untuk menyicil esai, latihan bahasa, dan kelengkapan dokumen.',
        };
    }

    /**
     * Recommend how to pace the preparation across the chosen horizon.
     */
    private function timelineRecommendation(QuickProfilingTimeline $timeline): string
    {
        return match ($timeline) {
            QuickProfilingTimeline::UnderThreeMonths => 'Target daftar kurang dari 3 bulan: kunci prioritas mingguan dan selesaikan dokumen wajib lebih dulu.',
            QuickProfilingTimeline::ThreeToSixMonths => 'Dalam 3–6 bulan, bagi persiapan menjadi tahap dokumen, esai, dan latihan dengan target kecil tiap bulan.',
            QuickProfilingTimeline::SixToTwelveMonths => 'Sepanjang 6–12 bulan, bangun kebiasaan persiapan bertahap dan evaluasi progresmu setiap bulan.',
            QuickProfilingTimeline::OverTwelveMonths => 'Dengan waktu lebih dari 12 bulan, perkuat modal jangka panjang seperti skor bahasa dan prestasi pendukung.',
        };
    }

    /**
     * Interpret the chosen timeline without inventing a specific deadline.
     */
    private function timelineSummary(QuickProfilingTimeline $timeline): string
    {
        return match ($timeline) {
            QuickProfilingTimeline::UnderThreeMonths => 'Kamu ingin mendaftar dalam kurang dari 3 bulan. Waktunya singkat, jadi fokus pada dokumen wajib dan satu target utama.',
            QuickProfilingTimeline::ThreeToSixMonths => 'Kamu punya sekitar 3–6 bulan untuk bersiap. Cukup untuk menyusun esai dan melengkapi persyaratan bila dijalankan konsisten.',
            QuickProfilingTimeline::SixToTwelveMonths => 'Ada 6–12 bulan untuk bersiap. Gunakan waktu ini untuk membangun modal secara bertahap tanpa terburu-buru.',
            QuickProfilingTimeline::OverTwelveMonths => 'Kamu punya lebih dari 12 bulan. Manfaatkan waktu panjang ini untuk memperkuat skor bahasa dan prestasi pendukung.',
        };
    }
}
