<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Resources\Pages\EditRecord;

/**
 * Edit page of the Paket Pro resource (ProductResource).
 */
class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;
}
