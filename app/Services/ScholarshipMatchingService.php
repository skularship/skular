<?php

namespace App\Services;

use App\Contracts\ScholarshipMatchingServiceInterface;
use App\Data\MatchReasonData;
use App\Data\ScholarshipData;
use App\Data\ScholarshipMatchData;
use App\Data\UserProfileData;
use App\Enums\MatchTier;
use App\Enums\ScholarshipStatus;
use App\Models\Scholarship;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ScholarshipMatchingService implements ScholarshipMatchingServiceInterface
{
    /**
     * Weight of each core eligibility criterion in the match score.
     */
    private const WEIGHT_GPA = 40;

    private const WEIGHT_AGE = 30;

    private const WEIGHT_SKTM = 30;

    /**
     * Bonuses applied when the scholarship also fits the mentee's preferences.
     */
    private const BONUS_CLUSTER = 10;

    private const BONUS_DESIRED_LEVEL = 5;

    /**
     * Penalties applied when the registration window is tight or already closed.
     */
    private const PENALTY_DEADLINE_SOON = 10;

    private const PENALTY_DEADLINE_PASSED = 25;

    /**
     * Number of days left that counts as an urgent deadline.
     */
    private const URGENT_DEADLINE_DAYS = 7;

    /**
     * Match available scholarships against the mentee profile.
     *
     * Education level and location scope are hard filters. GPA, age, SKTM,
     * deadline, cluster and desired level only affect the score and reasons so
     * the mentee always sees why a scholarship is (not) a fit.
     *
     * @return Collection<int, ScholarshipMatchData>
     */
    public function match(UserProfileData $profile_data): Collection
    {
        $candidates = Scholarship::query()
            ->where('status', ScholarshipStatus::Active)
            ->whereIn('level', $profile_data->level->matchingLevelValues())
            ->where(function (Builder $query) use ($profile_data): void {
                $query->whereNull('scope')->orWhere('scope', $profile_data->desired_location);
            })
            ->orderBy('closing_date')
            ->get();

        return $candidates
            ->map(fn (Scholarship $scholarship): ScholarshipMatchData => $this->evaluate($profile_data, $scholarship))
            ->sortByDesc(fn (ScholarshipMatchData $match): array => [$match->tier->weight(), $match->score])
            ->values();
    }

    /**
     * Score a single scholarship against the mentee profile.
     */
    private function evaluate(UserProfileData $profile_data, Scholarship $scholarship): ScholarshipMatchData
    {
        $reasons = [];
        $earned = 0;
        $available = 0;
        $meets_eligibility = true;

        // Grade point average.
        $minimum_gpa = $scholarship->minimumGpa();

        if ($minimum_gpa === null) {
            $reasons[] = new MatchReasonData('IPK', 'Tidak ada batasan IPK minimum.', true, informational: true);
        } else {
            $available += self::WEIGHT_GPA;
            $gpa = $profile_data->gpa;
            $gpa_satisfied = $gpa !== null && $gpa >= $minimum_gpa;

            if ($gpa_satisfied) {
                $earned += self::WEIGHT_GPA;
            }

            $reasons[] = new MatchReasonData(
                'IPK',
                sprintf(
                    'IPK Anda (%s) %s syarat minimum (%s).',
                    $gpa === null ? 'belum tersedia' : number_format($gpa, 2),
                    $gpa_satisfied ? 'memenuhi' : 'belum memenuhi',
                    number_format($minimum_gpa, 2),
                ),
                $gpa_satisfied,
            );

            if (! $gpa_satisfied) {
                $meets_eligibility = false;
            }
        }

        // Age.
        $maximum_age = $scholarship->maximum_age;

        if ($maximum_age === null) {
            $reasons[] = new MatchReasonData('Usia', 'Tidak ada batasan usia maksimal.', true, informational: true);
        } else {
            $available += self::WEIGHT_AGE;
            $age_satisfied = $profile_data->age > 0 && $profile_data->age <= $maximum_age;

            if ($age_satisfied) {
                $earned += self::WEIGHT_AGE;
            }

            $reasons[] = new MatchReasonData(
                'Usia',
                $age_satisfied
                    ? sprintf('Usia Anda (%d tahun) memenuhi syarat maksimal (%d tahun).', $profile_data->age, $maximum_age)
                    : sprintf('Usia Anda (%d tahun) melebihi batas maksimal (%d tahun).', $profile_data->age, $maximum_age),
                $age_satisfied,
            );

            if (! $age_satisfied) {
                $meets_eligibility = false;
            }
        }

        // SKTM / KHI letter.
        if ($scholarship->sktm_require) {
            $available += self::WEIGHT_SKTM;
            $sktm_satisfied = $profile_data->has_sktm;

            if ($sktm_satisfied) {
                $earned += self::WEIGHT_SKTM;
            }

            $reasons[] = new MatchReasonData(
                'SKTM/KHI',
                $sktm_satisfied
                    ? 'Anda memiliki SKTM/KHI yang dipersyaratkan.'
                    : 'Beasiswa ini memerlukan SKTM/KHI yang belum Anda miliki.',
                $sktm_satisfied,
            );

            if (! $sktm_satisfied) {
                $meets_eligibility = false;
            }
        } else {
            $reasons[] = new MatchReasonData('SKTM/KHI', 'Tidak memerlukan SKTM/KHI.', true, informational: true);
        }

        // Bonuses.
        $bonus = 0;

        if ($scholarship->cluster_id !== null && $scholarship->cluster_id === $profile_data->cluster_id) {
            $bonus += self::BONUS_CLUSTER;
            $reasons[] = new MatchReasonData(
                'Bidang',
                sprintf('Bidang %s sesuai dengan minatmu.', $profile_data->cluster_name ?? 'studi'),
                true,
                informational: true,
            );
        }

        if ($scholarship->level === $profile_data->desired_level) {
            $bonus += self::BONUS_DESIRED_LEVEL;
            $reasons[] = new MatchReasonData(
                'Jenjang tujuan',
                sprintf('Sesuai dengan jenjang %s yang kamu incar.', $scholarship->level->label()),
                true,
                informational: true,
            );
        }

        // Education level is always satisfied because it is a hard filter.
        $reasons[] = new MatchReasonData(
            'Jenjang',
            sprintf('Jenjang %s sesuai dengan beasiswa ini.', $scholarship->level->label()),
            true,
            informational: true,
        );

        if ($scholarship->scope !== null) {
            $reasons[] = new MatchReasonData(
                'Lokasi',
                sprintf('Beasiswa %s sesuai dengan preferensimu.', mb_strtolower($scholarship->scope->label())),
                true,
                informational: true,
            );
        }

        // Deadline.
        $penalty = 0;
        $deadline_urgent = false;
        $days_remaining = $scholarship->daysRemaining();

        if ($scholarship->closing_date !== null) {
            if (! $scholarship->isOpen()) {
                $penalty += self::PENALTY_DEADLINE_PASSED;
                $deadline_urgent = true;
                $reasons[] = new MatchReasonData('Deadline', 'Pendaftaran sudah ditutup.', false);
            } elseif ($days_remaining !== null && $days_remaining <= self::URGENT_DEADLINE_DAYS) {
                $penalty += self::PENALTY_DEADLINE_SOON;
                $deadline_urgent = true;
                $reasons[] = new MatchReasonData(
                    'Deadline',
                    sprintf('Pendaftaran ditutup dalam %d hari. Segera siapkan berkasmu.', $days_remaining),
                    false,
                );
            } else {
                $reasons[] = new MatchReasonData(
                    'Deadline',
                    sprintf('Masih ada %d hari untuk mendaftar.', $days_remaining ?? 0),
                    true,
                    informational: true,
                );
            }
        }

        $base_score = $available === 0 ? 100 : (int) round($earned / $available * 100);
        $score = max(0, min(100, $base_score + $bonus - $penalty));

        $tier = match (true) {
            ! $meets_eligibility => MatchTier::Low,
            $deadline_urgent => MatchTier::Good,
            default => MatchTier::VeryGood,
        };

        return new ScholarshipMatchData(
            scholarship: ScholarshipData::fromModel($scholarship),
            score: $score,
            tier: $tier,
            reasons: $reasons,
        );
    }
}
