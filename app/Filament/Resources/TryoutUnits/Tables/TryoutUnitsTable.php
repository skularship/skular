<?php

namespace App\Filament\Resources\TryoutUnits\Tables;

use App\Enums\ContentStatus;
use App\Enums\TryoutSkill;
use App\Models\TryoutUnit;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * List table of the Try Out units: code, skill, title, audio present, question count, status.
 */
class TryoutUnitsTable
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
            ->modifyQueryUsing(fn ($query) => $query->withCount('questions'))
            ->defaultSort('code')
            ->columns([
                TextColumn::make('code')->label('Kode')->searchable()->sortable(),
                TextColumn::make('skill')->label('Subtes')->badge(),
                TextColumn::make('title')->label('Judul')->searchable()->wrap(),
                TextColumn::make('media')->label('Media')->state(fn (TryoutUnit $record): string => collect([
                    filled($record->audio_path) ? 'Audio' : null,
                    filled($record->passage) ? 'Teks' : null,
                    filled($record->image_path) ? 'Peta' : null,
                ])->filter()->implode(' · ') ?: '-'),
                TextColumn::make('questions_count')->label('Soal')->sortable(),
                TextColumn::make('status')->label('Status')->badge(),
            ])
            ->filters([
                SelectFilter::make('skill')->label('Subtes')->options(TryoutSkill::options()),
                SelectFilter::make('status')->label('Status')->options(ContentStatus::options()),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->hidden(fn (TryoutUnit $record): bool => $record->questions()->exists() || $record->sections()->exists()),
            ])
            ->emptyStateHeading('Belum ada unit')
            ->emptyStateDescription('Unit = satu teks Reading atau satu audio Listening beserta soalnya.')
            ->emptyStateIcon('heroicon-o-speaker-wave');
    }
}
