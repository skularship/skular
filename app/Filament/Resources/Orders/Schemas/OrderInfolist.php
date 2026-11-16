<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Support\Rupiah;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Read-only detail of one order.
 */
class OrderInfolist
{
    /**
     * Add the entries to the schema.
     *
     * @param  Schema  $schema  The schema to configure.
     * @return Schema The configured schema.
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Pesanan')
                    ->schema([
                        TextEntry::make('order_id')->label('Order ID')->copyable(),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn ($state): string => $state->getLabel())
                            ->color(fn ($state): string => match ($state->getColor()) {
                                'success' => 'success',
                                'danger' => 'danger',
                                'warning' => 'warning',
                                default => 'gray',
                            }),
                        TextEntry::make('user.name')->label('Mentee'),
                        TextEntry::make('user.email')->label('Email')->copyable(),
                        TextEntry::make('product.name')->label('Paket'),
                        TextEntry::make('amount')->label('Dibayar')->formatStateUsing(fn (int $state): string => Rupiah::format($state)),
                        TextEntry::make('payment_type')->label('Metode')->placeholder('-'),
                        TextEntry::make('paid_at')->label('Dibayar pada')->dateTime()->placeholder('-'),
                        TextEntry::make('created_at')->label('Dibuat')->dateTime(),
                        TextEntry::make('midtrans_transaction_id')->label('ID transaksi Midtrans')->placeholder('-')->copyable(),
                    ])
                    ->columns(2),
                Section::make('Referral')
                    ->schema([
                        TextEntry::make('promo_code')->label('Kode dipakai')->placeholder('-'),
                        TextEntry::make('referrer.name')->label('Pemilik kode')->placeholder('-'),
                        TextEntry::make('discount_amount')->label('Diskon')->formatStateUsing(fn (int $state): string => Rupiah::format($state)),
                    ])
                    ->columns(3),
            ]);
    }
}
