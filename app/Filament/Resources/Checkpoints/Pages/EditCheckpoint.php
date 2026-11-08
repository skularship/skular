<?php

namespace App\Filament\Resources\Checkpoints\Pages;

use App\Filament\Resources\Checkpoints\CheckpointResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

/**
 * Edit page of the Checkpoint mentee resource (CheckpointResource).
 */
class EditCheckpoint extends EditRecord
{
    protected static string $resource = CheckpointResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
