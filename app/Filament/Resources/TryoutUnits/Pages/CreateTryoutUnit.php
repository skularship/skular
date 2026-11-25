<?php

namespace App\Filament\Resources\TryoutUnits\Pages;

use App\Filament\Resources\TryoutUnits\TryoutUnitResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Create page of the Try Out units (TryoutUnitResource).
 */
class CreateTryoutUnit extends CreateRecord
{
    protected static string $resource = TryoutUnitResource::class;
}
