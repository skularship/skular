<?php

namespace App\Filament\Resources\PracticeForms\Pages;

use App\Filament\Resources\PracticeForms\PracticeFormResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

/**
 * Edit page of the Latihan praktik resource (PracticeFormResource).
 */
class EditPracticeForm extends EditRecord
{
    protected static string $resource = PracticeFormResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
