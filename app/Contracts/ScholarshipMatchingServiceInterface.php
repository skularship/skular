<?php

namespace App\Contracts;

use App\Data\ScholarshipMatchData;
use App\Data\UserProfileData;
use Illuminate\Support\Collection;

interface ScholarshipMatchingServiceInterface
{
    /**
     * Match available scholarships against the mentee profile.
     *
     * @return Collection<int, ScholarshipMatchData>
     */
    public function match(UserProfileData $profile_data): Collection;
}
