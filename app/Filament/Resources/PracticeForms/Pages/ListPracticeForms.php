<?php

namespace App\Filament\Resources\PracticeForms\Pages;

use App\Filament\Resources\PracticeForms\PracticeFormResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPracticeForms extends ListRecords
{
    protected static string $resource = PracticeFormResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
