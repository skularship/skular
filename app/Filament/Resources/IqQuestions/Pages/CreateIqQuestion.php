<?php

namespace App\Filament\Resources\IqQuestions\Pages;

use App\Filament\Resources\IqQuestions\IqQuestionResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Create page of the Bank Soal IQ (IqQuestionResource).
 */
class CreateIqQuestion extends CreateRecord
{
    protected static string $resource = IqQuestionResource::class;
}
