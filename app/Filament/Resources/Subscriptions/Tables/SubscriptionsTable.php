<?php

namespace App\Filament\Resources\Subscriptions\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * List table of the Langganan Pro resource.
 */
class SubscriptionsTable
{
    /**
     * Add columns, filters and actions to the table.
     *
     * @param  Table  $table  The table to configure.
     * @return Table The configured table.
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('active_until')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('user'))
            ->columns([
                TextColumn::make('user.email')->label('Mentee')->searchable()->wrap(),
                TextColumn::make('plan')->label('Paket')->badge()->color('gray'),
                TextColumn::make('active_until')->label('Aktif sampai')->dateTime()->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->state(fn ($record): string => $record->active_until->isFuture() ? 'Aktif' : 'Berakhir')
                    ->color(fn (string $state): string => $state === 'Aktif' ? 'success' : 'gray'),
            ])
            ->filters([
                Filter::make('aktif')
                    ->label('Hanya yang aktif')
                    ->query(fn (Builder $query): Builder => $query->where('active_until', '>', now()))
                    ->default(),
                Filter::make('segera_berakhir')
                    ->label('Berakhir dalam 7 hari')
                    ->query(fn (Builder $query): Builder => $query->whereBetween('active_until', [now(), now()->addDays(7)])),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
