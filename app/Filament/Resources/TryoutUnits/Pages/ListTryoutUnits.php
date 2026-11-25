<?php

namespace App\Filament\Resources\TryoutUnits\Pages;

use App\Filament\Resources\TryoutUnits\TryoutUnitResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

/**
 * List page of the Try Out units (TryoutUnitResource).
 */
class ListTryoutUnits extends ListRecords
{
    protected static string $resource = TryoutUnitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
