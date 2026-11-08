<?php

namespace App\Contracts;

use App\Data\CvSectionData;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Builds the mentee CV page from practice answers whose form is flagged "include in CV".
 */
interface CvServiceInterface
{
    /**
     * Build the mentee CV sections from practice answers flagged by the admin.
     *
     * @return Collection<int, CvSectionData>
     */
    public function sections(User $user): Collection;
}
