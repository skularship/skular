<?php

namespace App\Filament\Resources\UserPracticeResponses\Pages;

use App\Filament\Resources\UserPracticeResponses\UserPracticeResponseResource;
use Filament\Resources\Pages\ListRecords;

/**
 * List page of the Jawaban latihan resource (UserPracticeResponseResource).
 */
class ListUserPracticeResponses extends ListRecords
{
    protected static string $resource = UserPracticeResponseResource::class;
}
