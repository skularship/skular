<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * List table of the Paket Pro resource.
 */
class ProductsTable
{
    /**
     * Add columns and actions to the table.
     *
     * @param  Table  $table  The table to configure.
     * @return Table The configured table.
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id')
            ->columns([
                TextColumn::make('name')->label('Paket')->weight('semibold'),
                TextColumn::make('code')->label('Kode')->badge()->color('gray'),
                TextColumn::make('price_normal')->label('Harga normal')->money('IDR', locale: 'id')->sortable(),
                TextColumn::make('price_launching')->label('Harga launching')->money('IDR', locale: 'id')->placeholder('-'),
                TextColumn::make('duration_days')->label('Lama aktif')->suffix(' hari'),
                IconColumn::make('is_active')->label('Dijual')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
