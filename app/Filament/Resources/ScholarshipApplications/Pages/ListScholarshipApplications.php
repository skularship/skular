<?php

namespace App\Filament\Resources\ScholarshipApplications\Pages;

use App\Filament\Resources\ScholarshipApplications\ScholarshipApplicationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

/**
 * List page of the Pendaftaran mentee resource (ScholarshipApplicationResource).
 */
class ListScholarshipApplications extends ListRecords
{
    protected static string $resource = ScholarshipApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
