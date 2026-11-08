<?php

namespace App\Filament\Resources\Scholarships\Pages;

use App\Filament\Resources\Scholarships\ScholarshipResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Create page of the Beasiswa resource (ScholarshipResource).
 */
class CreateScholarship extends CreateRecord
{
    protected static string $resource = ScholarshipResource::class;
}
