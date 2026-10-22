<?php

namespace App\Filament\Resources\PracticeForms\Tables;

use App\Models\PracticeForm;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class PracticeFormsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('ordering')
            ->columns([
                TextColumn::make('title')
                    ->label('Judul latihan')
                    ->searchable()
                    ->wrap()
                    ->weight('semibold'),
                TextColumn::make('module.title')
                    ->label('Modul')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('fields')
                    ->label('Jumlah pertanyaan')
                    ->state(fn (PracticeForm $record): int => count($record->fields ?? []))
                    ->badge()
                    ->color(fn (int $state): string => $state === 0 ? 'danger' : 'success'),
                IconColumn::make('is_active')
                    ->label('Tampil')
                    ->boolean(),
                IconColumn::make('include_in_cv')
                    ->label('Masuk CV')
                    ->boolean(),
                TextColumn::make('ordering')
                    ->label('Urutan')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('cv_ordering')
                    ->label('Urutan di CV')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('module_id')
                    ->label('Modul')
                    ->relationship('module', 'title')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('is_active')
                    ->label('Tampil ke mentee'),
                TernaryFilter::make('include_in_cv')
                    ->label('Tampil di CV'),
            ])
            ->recordActions([
                EditAction::make()
                    ->modalWidth('5xl'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum ada latihan praktik')
            ->emptyStateDescription('Latihan praktik adalah tempat mentee berlatih menulis jawaban yang dinilai AI. Buat latihan dari modul terkait agar konteksnya jelas.')
            ->emptyStateIcon('heroicon-o-clipboard-document-check');
    }
}
