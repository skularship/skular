<?php

namespace App\Filament\Resources\Events\Pages;

use App\Filament\Resources\Events\EventResource;
use Filament\Resources\Pages\EditRecord;

/**
 * Edit page of the Event resource (EventResource), with the participants table below the form.
 */
class EditEvent extends EditRecord
{
    protected static string $resource = EventResource::class;
}
