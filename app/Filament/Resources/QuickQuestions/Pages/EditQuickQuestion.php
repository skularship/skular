<?php

namespace App\Filament\Resources\QuickQuestions\Pages;

use App\Filament\Resources\QuickQuestions\QuickQuestionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditQuickQuestion extends EditRecord
{
    protected static string $resource = QuickQuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
