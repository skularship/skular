<?php

namespace App\Filament\Resources\Tryouts\Pages;

use App\Filament\Resources\Tryouts\TryoutResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

/**
 * List page of the Tryout resource (TryoutResource).
 */
class ListTryouts extends ListRecords
{
    protected static string $resource = TryoutResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
