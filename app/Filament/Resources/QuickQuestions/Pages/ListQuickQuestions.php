<?php

namespace App\Filament\Resources\QuickQuestions\Pages;

use App\Filament\Resources\QuickQuestions\QuickQuestionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

/**
 * List page of the Quick Question resource (QuickQuestionResource).
 */
class ListQuickQuestions extends ListRecords
{
    protected static string $resource = QuickQuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
