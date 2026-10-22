<?php

namespace App\Filament\Resources\TwibbonTemplates\Pages;

use App\Filament\Resources\TwibbonTemplates\TwibbonTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTwibbonTemplates extends ListRecords
{
    protected static string $resource = TwibbonTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
