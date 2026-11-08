<?php

namespace App\Contracts;

use App\Data\ScholarshipMatchData;
use App\Data\UserProfileData;
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
}
