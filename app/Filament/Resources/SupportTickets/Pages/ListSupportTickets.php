<?php

namespace App\Filament\Resources\SupportTickets\Pages;

use App\Filament\Resources\SupportTickets\SupportTicketResource;
use Filament\Resources\Pages\ListRecords;

/**
 * List page of the Tiket bantuan resource (SupportTicketResource).
 */
class ListSupportTickets extends ListRecords
{
    protected static string $resource = SupportTicketResource::class;
}
