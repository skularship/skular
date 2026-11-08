<?php

namespace App\Filament\Resources\ScholarshipApplications\Tables;

use App\Enums\ApplicationResult;
use App\Enums\ApplicationStatus;
use App\Models\ScholarshipApplication;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * List table of the Pendaftaran mentee resource (columns, filters and actions).
 */
class ScholarshipApplicationsTable
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
                    ->label('Mentee')
                    ->searchable(),
                TextColumn::make('scholarship.scholarship_name')
                    ->label('Beasiswa')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->searchable(),
                IconColumn::make('is_primary')
                    ->boolean()
                    ->hiddenFrom('md'),
                TextColumn::make('selected_at')
                    ->dateTime()
                    ->sortable()
                    ->hiddenFrom('lg'),
                TextColumn::make('submitted_at')
                    ->dateTime()
                    ->sortable()
                    ->hiddenFrom('md'),
                TextColumn::make('submission_proof')
                    ->label('Bukti')
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? 'Lihat bukti' : '—')
                    ->url(fn (ScholarshipApplication $record): ?string => $record->hasSubmissionProof()
                        ? route('applications.proof', $record)
                        : null)
                    ->openUrlInNewTab(),
                TextColumn::make('submission_confirmed_at')
                    ->dateTime()
                    ->sortable()
                    ->hiddenFrom('lg'),
                TextColumn::make('result')
                    ->badge()
                    ->searchable(),
                TextColumn::make('result_reported_at')
                    ->dateTime()
                    ->sortable()
                    ->hiddenFrom('lg'),
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
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(ApplicationStatus::options()),
                SelectFilter::make('result')
                    ->label('Hasil')
                    ->options(ApplicationResult::options()),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum ada pendaftaran beasiswa')
            ->emptyStateDescription('Pendaftaran muncul setelah mentee memilih beasiswa dan mulai belajar. Belum ada mentee yang mendaftar saat ini.')
            ->emptyStateIcon('heroicon-o-document-arrow-up');
    }
}
