<?php

namespace App\Filament\Resources\TwibbonTemplates\Pages;

use App\Filament\Resources\TwibbonTemplates\TwibbonTemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

/**
 * Edit page of the Template Twibbon resource (TwibbonTemplateResource).
 */
class EditTwibbonTemplate extends EditRecord
{
    protected static string $resource = TwibbonTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
