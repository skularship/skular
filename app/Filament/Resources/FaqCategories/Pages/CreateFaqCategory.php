<?php

namespace App\Filament\Resources\FaqCategories\Pages;

use App\Filament\Resources\FaqCategories\FaqCategoryResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Create page of the Kategori FAQ resource (FaqCategoryResource).
 */
class CreateFaqCategory extends CreateRecord
{
    protected static string $resource = FaqCategoryResource::class;
}
