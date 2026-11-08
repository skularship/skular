<?php

namespace App\Filament\Resources\QuickQuestions\Pages;

use App\Filament\Resources\QuickQuestions\QuickQuestionResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Create page of the Quick Question resource (QuickQuestionResource).
 */
class CreateQuickQuestion extends CreateRecord
{
    protected static string $resource = QuickQuestionResource::class;
}
