<?php

namespace App\Filament\Resources\ModuleFeedback\Pages;

use App\Filament\Resources\ModuleFeedback\ModuleFeedbackResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

/**
 * List page of the Feedback modul resource (ModuleFeedbackResource).
 */
class ListModuleFeedback extends ListRecords
{
    protected static string $resource = ModuleFeedbackResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
