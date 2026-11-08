<?php

namespace App\Filament\Resources\Fases\Pages;

use App\Filament\Resources\Fases\FaseResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

/**
 * Edit page of the Fase pembelajaran resource (FaseResource).
 */
class EditFase extends EditRecord
{
    protected static string $resource = FaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->failureNotificationTitle('Fase tidak bisa dihapus karena modulnya sudah dikerjakan mentee.'),
        ];
    }
}
