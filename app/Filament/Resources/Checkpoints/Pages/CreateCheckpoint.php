<?php

namespace App\Filament\Resources\Checkpoints\Pages;

use App\Filament\Resources\Checkpoints\CheckpointResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Create page of the Checkpoint mentee resource (CheckpointResource).
 */
class CreateCheckpoint extends CreateRecord
{
    protected static string $resource = CheckpointResource::class;
}
