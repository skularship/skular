<?php

namespace App\Filament\Resources\Checkpoints\Tables;

use App\Enums\CheckpointStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * List table of the Checkpoint mentee resource (columns, filters and actions).
 */
class CheckpointsTable
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
                TextColumn::make('user.name')
                    ->searchable(),
                TextColumn::make('module.title')
                    ->searchable(),
                TextColumn::make('fase.title')
                    ->searchable()
                    ->hiddenFrom('md'),
                TextColumn::make('status')
                    ->badge()
                    ->searchable(),
                TextColumn::make('score')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('attempts')
                    ->numeric()
                    ->sortable()
                    ->hiddenFrom('md'),
                TextColumn::make('available_at')
                    ->dateTime()
                    ->sortable()
                    ->hiddenFrom('lg'),
                TextColumn::make('started_at')
                    ->dateTime()
                    ->sortable()
                    ->hiddenFrom('lg'),
                TextColumn::make('completed_at')
                    ->dateTime()
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
            ->stackedOnMobile()
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(CheckpointStatus::options()),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum ada checkpoint')
            ->emptyStateDescription('Checkpoint terbentuk otomatis ketika mentee membuka modul. Belum ada mentee yang memulai modul saat ini.')
            ->emptyStateIcon('heroicon-o-flag');
    }
}
