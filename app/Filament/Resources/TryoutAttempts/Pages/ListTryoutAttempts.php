<?php

namespace App\Filament\Resources\TryoutAttempts\Pages;

use App\Filament\Resources\TryoutAttempts\TryoutAttemptResource;
use Filament\Resources\Pages\ListRecords;

/**
 * List page of the Try Out attempts (read only).
 */
class ListTryoutAttempts extends ListRecords
{
    protected static string $resource = TryoutAttemptResource::class;
}
