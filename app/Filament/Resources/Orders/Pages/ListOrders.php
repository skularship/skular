<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use Filament\Resources\Pages\ListRecords;

/**
 * List page of the Pesanan resource (OrderResource).
 */
class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;
}
