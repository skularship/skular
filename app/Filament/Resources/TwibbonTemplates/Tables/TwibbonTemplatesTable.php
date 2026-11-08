<?php

namespace App\Filament\Resources\TwibbonTemplates\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * List table of the Template Twibbon resource (columns, filters and actions).
 */
class TwibbonTemplatesTable
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
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                ImageColumn::make('image_path'),
                TextColumn::make('font_path')
                    ->searchable(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('ordering')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum ada template twibbon')
            ->emptyStateDescription('Unggah template agar mentee bisa merayakan kelulusan beasiswanya dengan twibbon Skular.')
            ->emptyStateIcon('heroicon-o-photo')
            ->emptyStateActions([
                CreateAction::make()->label('Tambah template'),
            ]);
    }
}
