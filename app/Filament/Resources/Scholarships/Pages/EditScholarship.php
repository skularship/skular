<?php

namespace App\Filament\Resources\Scholarships\Pages;

use App\Filament\Resources\Scholarships\ScholarshipResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

/**
 * Edit page of the Beasiswa resource (ScholarshipResource).
 */
class EditScholarship extends EditRecord
{
    protected static string $resource = ScholarshipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->failureNotificationTitle('Beasiswa tidak bisa dihapus karena sudah dipilih mentee. Ubah statusnya menjadi ditutup.'),
        ];
    }
}
