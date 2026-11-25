<?php

namespace App\Filament\Resources\Tryouts\Pages;

use App\Enums\ContentStatus;
use App\Filament\Resources\Tryouts\TryoutResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Create page of the Tryout resource. A new Try Out always starts as a draft
 * and stamps theory_pdf_updated_at when a PDF is uploaded.
 */
class CreateTryout extends CreateRecord
{
    protected static string $resource = TryoutResource::class;

    /**
     * Force draft and stamp the PDF date.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = ContentStatus::Draft->value;

        if (filled($data['theory_pdf_path'] ?? null)) {
            $data['theory_pdf_updated_at'] = now();
        }

        return $data;
    }
}
