<?php

namespace App\Filament\Resources\Tryouts\Pages;

use App\Enums\ContentStatus;
use App\Filament\Actions\PublishTryoutAction;
use App\Filament\Resources\Tryouts\TryoutResource;
use App\Models\Tryout;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

/**
 * Edit page of the Tryout resource with the subtests below the form, the
 * "Terbitkan" / "Jadikan draf" actions, and replacement of the theory PDF
 * (the old file is deleted, theory_pdf_updated_at is stamped).
 */
class EditTryout extends EditRecord
{
    protected static string $resource = TryoutResource::class;

    /**
     * Previous theory PDF path, deleted after a replacement is saved.
     */
    private ?string $previous_pdf = null;

    protected function getHeaderActions(): array
    {
        return [
            PublishTryoutAction::make(),
            Action::make('unpublish')
                ->label('Jadikan draf')
                ->icon('heroicon-o-eye-slash')
                ->color('warning')
                ->requiresConfirmation()
                ->authorize('update')
                ->visible(fn (Tryout $record): bool => $record->status->isPublished())
                ->action(fn (Tryout $record) => $record->update(['status' => ContentStatus::Draft])),
            DeleteAction::make()->hidden(fn (Tryout $record): bool => $record->hasMenteeHistory()),
        ];
    }

    /**
     * Remember the old PDF and stamp the date when it changes.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var Tryout $record */
        $record = $this->getRecord();
        $new = $data['theory_pdf_path'] ?? null;

        if ($new !== $record->theory_pdf_path) {
            $this->previous_pdf = $record->theory_pdf_path;
            $data['theory_pdf_updated_at'] = filled($new) ? now() : null;
        }

        return $data;
    }

    /**
     * Delete the replaced theory PDF from the private disk.
     */
    protected function afterSave(): void
    {
        if (filled($this->previous_pdf)) {
            Storage::disk((string) config('tryout.theory_pdf.disk', 'local'))->delete((string) $this->previous_pdf);
        }
    }
}
