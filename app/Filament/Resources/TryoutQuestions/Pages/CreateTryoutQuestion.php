<?php

namespace App\Filament\Resources\TryoutQuestions\Pages;

use App\Filament\Resources\TryoutQuestions\TryoutQuestionResource;
use App\Models\TryoutUnit;
use Filament\Resources\Pages\CreateRecord;

/**
 * Create page of the Bank Soal; the exam type follows the unit.
 */
class CreateTryoutQuestion extends CreateRecord
{
    protected static string $resource = TryoutQuestionResource::class;

    /**
     * Copy the exam type from the unit.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $unit = TryoutUnit::query()->whereKey($data['tryout_unit_id'] ?? null)->first();
        $data['exam_type'] = $unit?->exam_type->value ?? 'ielts';

        return $data;
    }
}
