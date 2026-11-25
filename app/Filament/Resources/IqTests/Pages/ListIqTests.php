<?php

namespace App\Filament\Resources\IqTests\Pages;

use App\Filament\Resources\IqTests\IqTestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

/**
 * List page of the Tes IQ resource (IqTestResource).
 */
class ListIqTests extends ListRecords
{
    protected static string $resource = IqTestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
