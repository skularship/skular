<?php

namespace App\Filament\Resources\PaymentEvents;

use App\Filament\Resources\PaymentEvents\Pages\ListPaymentEvents;
use App\Filament\Resources\PaymentEvents\Pages\ViewPaymentEvent;
use App\Filament\Resources\PaymentEvents\Tables\PaymentEventsTable;
use App\Models\PaymentEvent;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Filament resource for PaymentEvent ("Notifikasi Midtrans") at /admin/payment-events.
 *
 * The raw audit log of every accepted notification. Read-only, except that an
 * admin with Update permission can queue an unprocessed event again.
 */
class PaymentEventResource extends Resource
{
    protected static ?string $model = PaymentEvent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBellAlert;

    protected static string|UnitEnum|null $navigationGroup = 'Pembayaran';

    protected static ?string $navigationLabel = 'Notifikasi Midtrans';

    protected static ?string $modelLabel = 'notifikasi';

    protected static ?string $pluralModelLabel = 'Notifikasi Midtrans';

    protected static ?int $navigationSort = 4;

    /**
     * Build the read-only detail view with the raw payload.
     *
     * @param  Schema  $schema  The schema provided by Filament.
     * @return Schema The configured schema.
     */
    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Notifikasi')
                ->schema([
                    TextEntry::make('order_id')->label('Order ID')->placeholder('-')->copyable(),
                    TextEntry::make('created_at')->label('Diterima')->dateTime(),
                    TextEntry::make('processed_at')->label('Diproses')->dateTime()->placeholder('Belum diproses'),
                    TextEntry::make('payload')
                        ->label('Isi mentah')
                        ->formatStateUsing(fn ($state): string => (string) json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))
                        ->extraAttributes(['class' => 'font-mono text-xs whitespace-pre-wrap'])
                        ->columnSpanFull(),
                ])
                ->columns(3),
        ]);
    }

    /**
     * Build the list table.
     *
     * @param  Table  $table  The table provided by Filament.
     * @return Table The configured table.
     */
    public static function table(Table $table): Table
    {
        return PaymentEventsTable::configure($table);
    }

    /**
     * Notifications only arrive from Midtrans.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * Get the pages (routes) of this resource.
     *
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListPaymentEvents::route('/'),
            'view' => ViewPaymentEvent::route('/{record}'),
        ];
    }
}
