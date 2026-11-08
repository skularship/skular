<?php

namespace App\Filament\Resources\Scholarships\Tables;

use App\Enums\EducationLevel;
use App\Enums\ScholarshipStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * List table of the Beasiswa resource (columns, filters and actions).
 */
class ScholarshipsTable
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
                TextColumn::make('scholarship_name')
                    ->searchable(),
                TextColumn::make('institution')
                    ->searchable(),
                TextColumn::make('level')
                    ->badge()
                    ->searchable(),
                TextColumn::make('location')
                    ->searchable()
                    ->hiddenFrom('md'),
                TextColumn::make('scope')
                    ->badge()
                    ->searchable()
                    ->hiddenFrom('md'),
                IconColumn::make('sktm_require')
                    ->boolean()
                    ->hiddenFrom('lg'),
                TextColumn::make('minimum_value')
                    ->numeric()
                    ->sortable()
                    ->hiddenFrom('md'),
                TextColumn::make('maximum_age')
                    ->numeric()
                    ->sortable()
                    ->hiddenFrom('lg'),
                TextColumn::make('opening_date')
                    ->date()
                    ->sortable()
                    ->hiddenFrom('lg'),
                TextColumn::make('closing_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('estimated_quota')
                    ->numeric()
                    ->sortable()
                    ->hiddenFrom('lg'),
                TextColumn::make('link')
                    ->searchable()
                    ->hiddenFrom('xl'),
                TextColumn::make('verification_date')
                    ->date()
                    ->sortable()
                    ->hiddenFrom('lg'),
                TextColumn::make('status')
                    ->badge()
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('cluster.name')
                    ->searchable()
                    ->hiddenFrom('xl'),
            ])
            ->stackedOnMobile()
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(ScholarshipStatus::options()),
                SelectFilter::make('level')
                    ->label('Jenjang')
                    ->options(EducationLevel::options()),
                SelectFilter::make('sktm_require')
                    ->label('Butuh SKTM')
                    ->options(['1' => 'Ya', '0' => 'Tidak']),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum ada beasiswa')
            ->emptyStateDescription('Tambahkan beasiswa pertama agar mentee bisa mulai mencocokkan profil dan menyiapkan pendaftaran.')
            ->emptyStateIcon('heroicon-o-academic-cap')
            ->emptyStateActions([
                CreateAction::make()->label('Tambah beasiswa'),
            ]);
    }
}
