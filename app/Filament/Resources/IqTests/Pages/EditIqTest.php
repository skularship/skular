<?php

namespace App\Filament\Resources\IqTests\Pages;

use App\Filament\Resources\IqTests\IqTestResource;
use App\Models\IqTest;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

/**
 * Edit page of the Tes IQ resource. A replaced material PDF is deleted from
 * the private disk (IqTest stamps material_pdf_updated_at). A test with
 * attempts cannot be deleted.
 */
class EditIqTest extends EditRecord
{
    protected static string $resource = IqTestResource::class;

    /**
     * Previous material PDF path, deleted after a replacement is saved.
     */
    private ?string $previous_pdf = null;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->hidden(fn (IqTest $record): bool => $record->attempts()->exists()),
        ];
    }

    /**
     * Remember the old PDF when it changes.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var IqTest $record */
        $record = $this->getRecord();

        if (($data['material_pdf_path'] ?? null) !== $record->material_pdf_path) {
            $this->previous_pdf = $record->material_pdf_path;
        }

        return $data;
    }

    /**
     * Delete the replaced material PDF from the private disk.
     */
    protected function afterSave(): void
    {
        if (filled($this->previous_pdf)) {
            Storage::disk((string) config('iq.material_pdf.disk', 'local'))->delete((string) $this->previous_pdf);
        }
    }
}
