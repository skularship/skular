<?php

namespace App\Filament\Resources\IqQuestions\Tables;

use App\Enums\IqSubtest;
use App\Models\IqQuestion;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

/**
 * List table of the Bank Soal IQ: number, subtest, prompt, image, key,
 * active, answered count; filters per test/subtest/active; activate (only
 * when the activation rules pass), deactivate, and delete for questions no
 * mentee saw.
 */
class IqQuestionsTable
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
            ->modifyQueryUsing(fn ($query) => $query->with('test')->withCount('answers'))
            ->defaultSort('number')
            ->columns([
                TextColumn::make('number')->label('No')->sortable(),
                TextColumn::make('subtest')->label('Subtes')->badge(),
                TextColumn::make('prompt')->label('Soal')->limit(60)->wrap()->searchable(),
                ImageColumn::make('image_path')->label('Gambar')->disk((string) config('iq.images.disk', 'public'))->toggleable(),
                TextColumn::make('answer_key')->label('Kunci'),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
                TextColumn::make('test.code')->label('Tes')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('answers_count')->label('Dipakai')->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('iq_test_id')->label('Tes')->relationship('test', 'title'),
                SelectFilter::make('subtest')->label('Subtes')->options(IqSubtest::options()),
                TernaryFilter::make('is_active')->label('Aktif'),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('activate')
                    ->label('Aktifkan')
                    ->icon('heroicon-o-eye')
                    ->color('success')
                    ->authorize('update')
                    ->visible(fn (IqQuestion $record): bool => ! $record->is_active)
                    ->action(function (IqQuestion $record): void {
                        $errors = $record->activationErrors();

                        if ($errors !== []) {
                            Notification::make()->title('Soal belum bisa diaktifkan')->body(implode(' ', $errors))->warning()->send();

                            return;
                        }

                        $record->update(['is_active' => true]);
                        Notification::make()->title('Soal aktif')->success()->send();
                    }),
                Action::make('deactivate')
                    ->label('Nonaktifkan')
                    ->icon('heroicon-o-eye-slash')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalDescription('Soal tidak muncul di tes baru. Percobaan yang sedang berjalan tetap memakai soal ini.')
                    ->authorize('update')
                    ->visible(fn (IqQuestion $record): bool => $record->is_active)
                    ->action(fn (IqQuestion $record) => $record->update(['is_active' => false])),
                DeleteAction::make()->hidden(fn (IqQuestion $record): bool => (int) $record->getAttribute('answers_count') > 0),
            ])
            ->emptyStateHeading('Bank soal IQ masih kosong')
            ->emptyStateDescription('Tambah soal satu per satu, atau impor bank soal JSON lewat tombol Impor JSON.')
            ->emptyStateIcon('heroicon-o-question-mark-circle');
    }
}
