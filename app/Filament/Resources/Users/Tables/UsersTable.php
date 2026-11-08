<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * List table of the Pengguna resource (columns, filters and actions).
 */
class UsersTable
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
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable()
                    ->hiddenFrom('md'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('two_factor_confirmed_at')
                    ->dateTime()
                    ->sortable()
                    ->hiddenFrom('xl'),
                TextColumn::make('google_id')
                    ->searchable()
                    ->hiddenFrom('xl'),
                TextColumn::make('avatar_url')
                    ->searchable()
                    ->hiddenFrom('xl'),
                TextColumn::make('level')
                    ->badge()
                    ->searchable(),
                TextColumn::make('age')
                    ->numeric()
                    ->sortable()
                    ->hiddenFrom('md'),
                TextColumn::make('gpa')
                    ->numeric()
                    ->sortable()
                    ->hiddenFrom('md'),
                IconColumn::make('has_sktm')
                    ->boolean()
                    ->hiddenFrom('lg'),
                TextColumn::make('desired_level')
                    ->badge()
                    ->searchable()
                    ->hiddenFrom('md'),
                TextColumn::make('desired_location')
                    ->badge()
                    ->searchable()
                    ->hiddenFrom('md'),
                TextColumn::make('cluster.name')
                    ->searchable()
                    ->hiddenFrom('lg'),
                TextColumn::make('phone')
                    ->searchable()
                    ->hiddenFrom('lg'),
                TextColumn::make('subscription')
                    ->searchable()
                    ->hiddenFrom('xl'),
                TextColumn::make('profile_completed_at')
                    ->dateTime()
                    ->sortable()
                    ->hiddenFrom('lg'),
            ])
            ->stackedOnMobile()
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
            ->emptyStateHeading('Belum ada pengguna')
            ->emptyStateDescription('Tambahkan pengguna pertama, atau tunggu mentee mendaftar melalui halaman registrasi.')
            ->emptyStateIcon('heroicon-o-users')
            ->emptyStateActions([
                CreateAction::make()->label('Tambah pengguna'),
            ]);
    }
}
