<?php

namespace App\Filament\Resources\Tryouts\Tables;

use App\Enums\ContentStatus;
use App\Enums\TryoutExamType;
use App\Filament\Actions\DuplicateTryoutAction;
use App\Models\Tryout;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * List table of the Tryout resource: title, exam, status, theory layers and attempt count.
 */
class TryoutsTable
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
            ->modifyQueryUsing(fn ($query) => $query->withCount('attempts'))
            ->defaultSort('ordering')
            ->columns([
                TextColumn::make('title')->label('Judul')->description(fn (Tryout $record): string => $record->code)->searchable()->wrap(),
                TextColumn::make('exam_type')->label('Jenis')->formatStateUsing(fn (TryoutExamType $state): string => $state->getLabel())->badge(),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('theory')
                    ->label('Teori')
                    ->state(fn (Tryout $record): string => collect([
                        $record->theory_module_id ? 'Modul' : null,
                        $record->theory_pdf_path ? 'PDF' : null,
                        $record->materials_url ? 'Link' : null,
                    ])->filter()->implode(' · ') ?: '-'),
                TextColumn::make('attempts_count')->label('Attempt')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Status')->options(ContentStatus::options()),
            ])
            ->recordActions([
                EditAction::make(),
                DuplicateTryoutAction::make(),
                DeleteAction::make()->hidden(fn (Tryout $record): bool => $record->hasMenteeHistory()),
            ])
            ->emptyStateHeading('Belum ada Try Out')
            ->emptyStateDescription('Buat paket Try Out, lalu tambahkan 4 subtes dan pilih unit soal di tab Subtes.')
            ->emptyStateIcon('heroicon-o-academic-cap');
    }
}
