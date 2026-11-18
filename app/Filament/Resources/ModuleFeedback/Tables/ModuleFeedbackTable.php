<?php

namespace App\Filament\Resources\ModuleFeedback\Tables;

use App\Enums\FeedbackDifficulty;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * List table of the Feedback modul resource (columns, filters and actions).
 */
class ModuleFeedbackTable
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
                    ->label('Modul')
                    ->searchable(),
                TextColumn::make('helpful_score')
                    ->label('Membantu (1-5)')
                    ->badge()
                    ->color(fn (?int $state): string => match (true) {
                        $state === null => 'gray',
                        $state >= 4 => 'success',
                        $state === 3 => 'warning',
                        default => 'danger',
                    })
                    ->placeholder('Form lama')
                    ->sortable(),
                TextColumn::make('difficulty')
                    ->label('Paling menyulitkan')
                    ->formatStateUsing(fn (?FeedbackDifficulty $state): string => $state?->getLabel() ?? '-')
                    ->toggleable(),
                TextColumn::make('open_answer')
                    ->label('Jawaban terbuka')
                    ->description(fn ($record): ?string => $record->open_question)
                    ->limit(80)
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('low_score_reason')
                    ->label('Alasan skor rendah')
                    ->limit(80)
                    ->wrap()
                    ->toggleable(),
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
                SelectFilter::make('helpful_score')
                    ->label('Skor membantu')
                    ->options([1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5']),
                SelectFilter::make('difficulty')
                    ->label('Paling menyulitkan')
                    ->options(FeedbackDifficulty::options()),
            ])
            ->defaultSort('updated_at', 'desc')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum ada feedback modul')
            ->emptyStateDescription('Feedback dikirim mentee setelah menyelesaikan modul. Belum ada feedback yang masuk saat ini.')
            ->emptyStateIcon('heroicon-o-chat-bubble-bottom-center-text');
    }
}
