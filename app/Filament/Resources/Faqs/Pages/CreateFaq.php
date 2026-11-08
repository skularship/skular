<?php

namespace App\Filament\Resources\Faqs\Pages;

use App\Filament\Resources\Faqs\FaqResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Create page of the FAQ resource (FaqResource).
 */
class CreateFaq extends CreateRecord
{
    protected static string $resource = FaqResource::class;
}
