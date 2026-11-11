<?php

namespace App\Contracts;

use App\Data\ScholarshipMatchData;
use App\Data\UserProfileData;
use App\Models\Scholarship;
use Illuminate\Support\Collection;

/**
 * Matches open scholarships against a mentee profile for the recommendation page.
 */
interface ScholarshipMatchingServiceInterface
{
    /**
     * Match available scholarships against the mentee profile.
     *
     * @return Collection<int, ScholarshipMatchData>
     */
    public function match(UserProfileData $profile_data): Collection;

    /**
     * Evaluate one scholarship for the mentee, as shown on its detail page.
     *
     * Applies the same hard filters as match() (active status, education
     * level, study-destination scope), so a scholarship the recommendation
     * page would never list returns null instead of misleading reasons.
     *
     * @param  UserProfileData  $profile_data  The mentee profile.
     * @param  Scholarship  $scholarship  The scholarship being viewed.
     * @return ScholarshipMatchData|null Tier and reasons, or null when the scholarship is not a candidate.
     */
    public function matchOne(UserProfileData $profile_data, Scholarship $scholarship): ?ScholarshipMatchData;
}
