<?php

namespace App\Filament\Resources\IqTests\Pages;

use App\Filament\Resources\IqTests\IqTestResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Create page of the Tes IQ resource (IqTestResource).
 */
class CreateIqTest extends CreateRecord
{
    protected static string $resource = IqTestResource::class;
}
