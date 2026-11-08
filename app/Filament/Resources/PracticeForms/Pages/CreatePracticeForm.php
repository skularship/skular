<?php

namespace App\Filament\Resources\PracticeForms\Pages;

use App\Filament\Resources\PracticeForms\PracticeFormResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Create page of the Latihan praktik resource (PracticeFormResource).
 */
class CreatePracticeForm extends CreateRecord
{
    protected static string $resource = PracticeFormResource::class;
}
