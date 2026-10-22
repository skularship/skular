<?php

namespace App\Filament\Resources\Fases\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FasesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('fase_order')
            ->columns([
                TextColumn::make('title')
                    ->label('Nama fase')
                    ->searchable()
                    ->wrap()
                    ->weight('semibold'),
                TextColumn::make('subtitle')
                    ->label('Subjudul')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('fase_order')
                    ->label('Urutan')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('modules_count')
                    ->label('Jumlah modul')
                    ->counts('modules')
                    ->badge()
                    ->color('info'),
                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum ada fase')
            ->emptyStateDescription('Fase adalah kelompok modul pada jalur belajar. Buat fase pertama sebelum menambahkan modul.')
            ->emptyStateIcon('heroicon-o-flag');
    }
}
