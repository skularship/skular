<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Resources\Pages\ListRecords;

/**
 * List page of the Paket Pro resource (ProductResource).
 */
class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;
}
