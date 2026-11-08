<?php

namespace App\Filament\Resources\ModuleFeedback\Pages;

use App\Filament\Resources\ModuleFeedback\ModuleFeedbackResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Create page of the Feedback modul resource (ModuleFeedbackResource).
 */
class CreateModuleFeedback extends CreateRecord
{
    protected static string $resource = ModuleFeedbackResource::class;
}
