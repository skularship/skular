<?php

namespace App\Filament\Resources\TryoutQuestions\Pages;

use App\Filament\Resources\TryoutQuestions\TryoutQuestionResource;
use App\Models\TryoutQuestion;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

/**
 * Edit page of the Bank Soal. Answered questions cannot be deleted (archive instead).
 */
class EditTryoutQuestion extends EditRecord
{
    protected static string $resource = TryoutQuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->hidden(fn (TryoutQuestion $record): bool => $record->hasAnswers()),
        ];
    }
}
