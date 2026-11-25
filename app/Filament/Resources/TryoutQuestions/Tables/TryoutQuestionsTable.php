<?php

namespace App\Filament\Resources\TryoutQuestions\Tables;

use App\Enums\ContentStatus;
use App\Enums\TryoutQuestionType;
use App\Enums\TryoutSkill;
use App\Models\TryoutQuestion;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

/**
 * List table of the Bank Soal: code, skill, level, type, unit and status,
 * filters per skill/level/type/status, duplicate, archive (answered) or delete.
 */
class TryoutQuestionsTable
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
            ->modifyQueryUsing(fn ($query) => $query->with('unit')->withCount('answers'))
            ->defaultSort('code')
            ->columns([
                TextColumn::make('code')->label('Kode')->searchable()->sortable(),
                TextColumn::make('skill')->label('Subtes')->badge(),
                TextColumn::make('level')->label('Level')->sortable(),
                TextColumn::make('question_type')->label('Tipe')->formatStateUsing(fn (TryoutQuestionType $state): string => $state->getLabel()),
                TextColumn::make('prompt')->label('Pertanyaan')->limit(60)->wrap()->searchable(),
                TextColumn::make('unit.code')->label('Unit')->placeholder('-'),
                TextColumn::make('answers_count')->label('Dijawab')->sortable()->toggleable(),
                TextColumn::make('status')->label('Status')->badge(),
            ])
            ->filters([
                SelectFilter::make('skill')->label('Subtes')->options(TryoutSkill::options()),
                SelectFilter::make('level')->label('Level')->options(TryoutQuestion::levelOptions()),
                SelectFilter::make('question_type')->label('Tipe')->options(TryoutQuestionType::options()),
                SelectFilter::make('status')->label('Status')->options(ContentStatus::options()),
            ])
            ->recordActions([
                EditAction::make(),
                ReplicateAction::make()
                    ->label('Duplikat')
                    ->excludeAttributes(['answers_count'])
                    ->beforeReplicaSaved(function (TryoutQuestion $replica): void {
                        $replica->code = Str::limit($replica->code, 26, '').'-'.Str::upper(Str::random(4));
                        $replica->status = ContentStatus::Draft;
                    }),
                Action::make('archive')
                    ->label('Arsipkan')
                    ->icon('heroicon-o-archive-box')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->authorize('update')
                    ->visible(fn (TryoutQuestion $record): bool => $record->hasAnswers() && $record->status->isPublished())
                    ->action(fn (TryoutQuestion $record) => $record->update(['status' => ContentStatus::Draft])),
                DeleteAction::make()->hidden(fn (TryoutQuestion $record): bool => $record->hasAnswers()),
            ])
            ->emptyStateHeading('Bank soal masih kosong')
            ->emptyStateDescription('Tambah soal satu per satu, atau impor bank soal JSON lewat tombol Impor JSON.')
            ->emptyStateIcon('heroicon-o-question-mark-circle');
    }
}
