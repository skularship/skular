<?php

namespace App\Filament\Resources\ModuleFeedback\Pages;

use App\Filament\Resources\ModuleFeedback\ModuleFeedbackResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditModuleFeedback extends EditRecord
{
    protected static string $resource = ModuleFeedbackResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
