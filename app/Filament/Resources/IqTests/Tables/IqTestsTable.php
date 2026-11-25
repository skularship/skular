<?php

namespace App\Filament\Resources\IqTests\Tables;

use App\Models\IqTest;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * List table of the Tes IQ: title and code, status, active/total
 * questions, material uploaded, attempts. A test with attempts cannot be deleted.
 */
class IqTestsTable
{
    /**
     * Add columns and actions to the table.
     *
     * @param  Table  $table  The table to configure.
     * @return Table The configured table.
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query
                ->withCount(['questions', 'attempts', 'questions as active_questions_count' => fn ($questions) => $questions->where('is_active', true)]))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('title')->label('Judul')->description(fn (IqTest $record): string => $record->code)->searchable()->wrap(),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('duration_minutes')->label('Durasi')->suffix(' menit'),
                TextColumn::make('active_questions_count')
                    ->label('Soal aktif')
                    ->formatStateUsing(fn (IqTest $record): string => $record->getAttribute('active_questions_count').' / '.$record->getAttribute('questions_count')),
                IconColumn::make('material_pdf_path')->label('Materi')->boolean()->getStateUsing(fn (IqTest $record): bool => $record->hasMaterialPdf()),
                TextColumn::make('attempts_count')->label('Percobaan')->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->hidden(fn (IqTest $record): bool => (int) $record->getAttribute('attempts_count') > 0),
            ])
            ->emptyStateHeading('Belum ada Tes IQ')
            ->emptyStateDescription('Buat tes baru, atau impor bank soal JSON di menu Bank Soal IQ.')
            ->emptyStateIcon('heroicon-o-light-bulb');
    }
}
