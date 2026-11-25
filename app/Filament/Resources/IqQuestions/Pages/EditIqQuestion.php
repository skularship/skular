<?php

namespace App\Filament\Resources\IqQuestions\Pages;

use App\Filament\Resources\IqQuestions\IqQuestionResource;
use App\Models\IqQuestion;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

/**
 * Edit page of the Bank Soal IQ. Questions mentees already saw cannot be
 * deleted (deactivate instead). A changed key applies to attempts closed
 * after the change; stored scores stay as they were.
 */
class EditIqQuestion extends EditRecord
{
    protected static string $resource = IqQuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->hidden(fn (IqQuestion $record): bool => $record->hasAnswers()),
        ];
    }
}
