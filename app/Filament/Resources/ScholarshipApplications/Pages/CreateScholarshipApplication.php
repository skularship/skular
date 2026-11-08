<?php

namespace App\Filament\Resources\ScholarshipApplications\Pages;

use App\Filament\Resources\ScholarshipApplications\ScholarshipApplicationResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Create page of the Pendaftaran mentee resource (ScholarshipApplicationResource).
 */
class CreateScholarshipApplication extends CreateRecord
{
    protected static string $resource = ScholarshipApplicationResource::class;
}
