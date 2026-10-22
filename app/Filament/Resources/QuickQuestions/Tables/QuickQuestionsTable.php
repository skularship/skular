<?php

namespace App\Filament\Resources\QuickQuestions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class QuickQuestionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('ordering')
            ->columns([
                TextColumn::make('question')
                    ->label('Pernyataan')
                    ->searchable()
                    ->wrap()
                    ->weight('semibold'),
                TextColumn::make('module.title')
                    ->label('Modul')
                    ->searchable()
                    ->wrap(),
                IconColumn::make('answer')
                    ->label('Kunci: Benar')
                    ->boolean(),
                TextColumn::make('ordering')
                    ->label('Urutan')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Tampil')
                    ->boolean(),
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
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum ada quick question')
            ->emptyStateDescription('Quick question bersifat opsional. Tambahkan bila modul perlu menguji pemahaman mentee sebelum latihan praktik.')
            ->emptyStateIcon('heroicon-o-question-mark-circle');
    }
}
