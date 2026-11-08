<?php

namespace App\Filament\Resources\TwibbonTemplates\Pages;

use App\Filament\Resources\TwibbonTemplates\TwibbonTemplateResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Create page of the Template Twibbon resource (TwibbonTemplateResource).
 */
class CreateTwibbonTemplate extends CreateRecord
{
    protected static string $resource = TwibbonTemplateResource::class;
}
