<?php

namespace App\Filament\Resources\PaymentEvents\Pages;

use App\Filament\Resources\PaymentEvents\PaymentEventResource;
use Filament\Resources\Pages\ListRecords;

/**
 * List page of the Notifikasi Midtrans resource (PaymentEventResource).
 */
class ListPaymentEvents extends ListRecords
{
    protected static string $resource = PaymentEventResource::class;
}
