<?php

namespace App\Filament\Resources\PracticeForms\Pages;

use App\Filament\Resources\PracticeForms\PracticeFormResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

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
