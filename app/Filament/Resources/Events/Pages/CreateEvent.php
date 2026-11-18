<?php

namespace App\Filament\Resources\Events\Pages;

use App\Filament\Resources\Events\EventResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Create page of the Event resource (EventResource).
 */
class CreateEvent extends CreateRecord
{
    protected static string $resource = EventResource::class;
}
