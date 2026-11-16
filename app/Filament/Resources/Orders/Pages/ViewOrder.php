<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use Filament\Resources\Pages\ViewRecord;

/**
 * Detail page of the Pesanan resource (OrderResource).
 */
class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;
}
