<?php

namespace App\Filament\Resources\ScholarshipApplications\Pages;

use App\Filament\Resources\ScholarshipApplications\ScholarshipApplicationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

/**
 * Edit page of the Pendaftaran mentee resource (ScholarshipApplicationResource).
 */
class EditScholarshipApplication extends EditRecord
{
    protected static string $resource = ScholarshipApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
