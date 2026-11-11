<?php

namespace App\Filament\Resources\SupportTickets;

use App\Enums\SupportTicketStatus;
use App\Filament\Resources\SupportTickets\Pages\ListSupportTickets;
use App\Filament\Resources\SupportTickets\Tables\SupportTicketsTable;
use App\Models\SupportTicket;
use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Filament resource for SupportTicket ("Tiket bantuan") at /admin/support-tickets.
 *
 * Admins read bug reports and help requests sent from the mentee "Bantuan" page, download the private
 * screenshot, and move tickets through Diterima → Sedang ditangani → Selesai/Ditutup with an internal note.
 * Access is governed by the matching Shield policy in app/Policies.
 */
class SupportTicketResource extends Resource
{
    protected static ?string $model = SupportTicket::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLifebuoy;

    protected static string|UnitEnum|null $navigationGroup = 'Pemantauan Mentee';

    protected static ?string $navigationLabel = 'Tiket bantuan';

    protected static ?string $modelLabel = 'tiket bantuan';

    protected static ?string $pluralModelLabel = 'Tiket bantuan';

    protected static ?int $navigationSort = 5;

    /**
     * Show how many tickets nobody has picked up yet.
     *
     * @return string|null Count of new tickets, or null to hide the badge.
     */
    public static function getNavigationBadge(): ?string
    {
        $count = SupportTicket::query()->where('status', SupportTicketStatus::New->value)->count();

        return $count > 0 ? (string) $count : null;
    }

    /**
     * Get the badge color of the navigation item.
     *
     * @return string Filament color name.
     */
    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    /**
     * Build the list table.
     *
     * @param  Table  $table  The table provided by Filament.
     * @return Table The configured table.
     */
    public static function table(Table $table): Table
    {
        return SupportTicketsTable::configure($table);
    }

    /**
     * Get the pages (routes) of this resource.
     *
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListSupportTickets::route('/'),
        ];
    }
}
