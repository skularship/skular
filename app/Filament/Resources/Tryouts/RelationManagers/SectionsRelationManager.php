<?php

namespace App\Filament\Resources\Tryouts\RelationManagers;

use App\Enums\ContentStatus;
use App\Enums\TryoutSkill;
use App\Models\Tryout;
use App\Models\TryoutQuestion;
use App\Models\TryoutSection;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Subtests of a Try Out (Listening, Reading, Writing, Speaking): duration in
 * minutes (stored in seconds), transfer time, question count, adaptive
 * start level, audio replays per mode, and the units of the pool. Subtests
 * of a Try Out with attempts cannot be deleted.
 */
class SectionsRelationManager extends RelationManager
{
    protected static string $relationship = 'sections';

    protected static ?string $title = 'Subtes';

    protected static ?string $modelLabel = 'subtes';

    protected static ?string $pluralModelLabel = 'Subtes';

    /**
     * Build the subtest form.
     *
     * @param  Schema  $schema  The schema provided by Filament.
     * @return Schema The configured schema.
     */
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('skill')
                    ->label('Subtes')
                    ->options(TryoutSkill::options())
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Set $set, ?string $state): void {
                        $skill = TryoutSkill::tryFrom((string) $state);

                        if ($skill === null) {
                            return;
                        }

                        $set('question_count', $skill->defaultQuestionCount());
                        $set('duration_seconds', $skill->defaultMinutes());
                        $set('is_adaptive', $skill->isObjective());
                        $set('transfer_seconds', $skill === TryoutSkill::Listening ? 10 : 0);
                        $set('ordering', array_search($skill, TryoutSkill::cases(), true) + 1);
                    }),
                TextInput::make('ordering')->label('Urutan')->numeric()->required()->default(1)->helperText('IELTS: 1 Listening, 2 Reading, 3 Writing, 4 Speaking.'),
                TextInput::make('duration_seconds')
                    ->label('Durasi (menit)')
                    ->numeric()
                    ->minValue(1)
                    ->required()
                    ->formatStateUsing(fn (?int $state): ?float => $state === null ? null : round($state / 60, 1))
                    ->dehydrateStateUsing(fn (mixed $state): int => (int) round((float) $state * 60)),
                TextInput::make('transfer_seconds')
                    ->label('Waktu cek ulang / pindah jawaban (menit)')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->helperText('Listening tes kertas: 10 menit. Boleh 0.')
                    ->formatStateUsing(fn (?int $state): ?float => $state === null ? null : round($state / 60, 1))
                    ->dehydrateStateUsing(fn (mixed $state): int => (int) round((float) $state * 60)),
                TextInput::make('question_count')->label('Jumlah soal / task / part')->numeric()->minValue(1)->required(),
                Toggle::make('is_adaptive')->label('Adaptif per soal')->helperText('Untuk Listening dan Reading.')->live(),
                Select::make('start_level')->label('Level awal')->options(TryoutQuestion::levelOptions())->default(3)->required(),
                TextInput::make('questions_per_unit')
                    ->label('Soal per unit (blok)')
                    ->numeric()
                    ->minValue(1)
                    ->helperText('Kosongkan untuk dihitung otomatis (jumlah soal ÷ jumlah unit).')
                    ->visible(fn (Get $get): bool => (bool) $get('is_adaptive')),
                TextInput::make('audio_plays_practice')->label('Putar audio maks (mode latihan)')->numeric()->minValue(1)->default(2)
                    ->visible(fn (Get $get): bool => $get('skill') === TryoutSkill::Listening->value || $get('skill') === TryoutSkill::Listening),
                TextInput::make('audio_plays_exam')->label('Putar audio maks (mode ujian)')->numeric()->minValue(1)->default(1)
                    ->visible(fn (Get $get): bool => $get('skill') === TryoutSkill::Listening->value || $get('skill') === TryoutSkill::Listening),
                Select::make('units')
                    ->label('Unit di pool subtes ini')
                    ->relationship(
                        'units',
                        'title',
                        fn (Builder $query, Get $get): Builder => $query->where('skill', $get('skill') instanceof TryoutSkill ? $get('skill')->value : (string) $get('skill')),
                    )
                    ->getOptionLabelFromRecordUsing(fn ($record): string => $record->code.' · '.$record->title.($record->status === ContentStatus::Draft ? ' (draf)' : ''))
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->helperText('Hanya unit dengan subtes yang sama. Unit draf tidak dipakai mentee.')
                    ->columnSpanFull(),
                Textarea::make('instructions')->label('Instruksi subtes')->rows(3)->columnSpanFull(),
            ])
            ->columns(2);
    }

    /**
     * Build the subtest table.
     *
     * @param  Table  $table  The table provided by Filament.
     * @return Table The configured table.
     */
    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('ordering')
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('units'))
            ->columns([
                TextColumn::make('ordering')->label('#'),
                TextColumn::make('skill')->label('Subtes')->badge(),
                TextColumn::make('duration_seconds')->label('Durasi')->formatStateUsing(fn (int $state, TryoutSection $record): string => $record->durationLabel()),
                TextColumn::make('question_count')->label('Soal'),
                IconColumn::make('is_adaptive')->label('Adaptif')->boolean(),
                TextColumn::make('units_count')->label('Unit'),
                TextColumn::make('pool')
                    ->label('Pool per level (1-5)')
                    ->state(fn (TryoutSection $record): string => $record->skill->isObjective() ? implode(' / ', $record->poolCountByLevel()) : '-'),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->hidden(fn (): bool => ! $this->getOwnerRecord() instanceof Tryout || $this->getOwnerRecord()->attempts()->exists()),
            ]);
    }
}
