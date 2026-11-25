<?php

namespace App\Filament\Resources\TryoutAttempts\Tables;

use App\Enums\TryoutAttemptStatus;
use App\Enums\TryoutMode;
use App\Enums\TryoutSkill;
use App\Models\TryoutAttempt;
use App\Models\TryoutAttemptSection;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * List table of Try Out attempts: mentee, Try Out, mode, status, band per subtest, overall and date.
 */
class TryoutAttemptsTable
{
    /**
     * Add columns, filters and actions to the table.
     *
     * @param  Table  $table  The table to configure.
     * @return Table The configured table.
     */
    public static function configure(Table $table): Table
    {
        $band = fn (TryoutSkill $skill) => TextColumn::make('band_'.$skill->value)
            ->label($skill->getLabel())
            ->state(function (TryoutAttempt $record) use ($skill): string {
                $band = $record->sections->first(fn (TryoutAttemptSection $section): bool => $section->skill === $skill)?->band;

                return $band === null ? '-' : number_format($band, 1, ',', '');
            });

        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['user', 'tryout', 'sections']))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('user.name')->label('Mentee')->description(fn (TryoutAttempt $record): string => $record->user->email)->searchable(),
                TextColumn::make('tryout.title')->label('Try Out')->wrap(),
                TextColumn::make('mode')->label('Mode')->formatStateUsing(fn (TryoutMode $state): string => $state->getLabel()),
                TextColumn::make('status')->label('Status')->badge(),
                $band(TryoutSkill::Listening),
                $band(TryoutSkill::Reading),
                $band(TryoutSkill::Writing),
                $band(TryoutSkill::Speaking),
                TextColumn::make('overall_band')->label('Overall')->formatStateUsing(fn (?float $state): string => $state === null ? '-' : number_format($state, 1, ',', ''))->sortable(),
                TextColumn::make('started_at')->label('Mulai')->dateTime('j M Y H:i', 'Asia/Jakarta')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Status')->options(TryoutAttemptStatus::options()),
                SelectFilter::make('tryout_id')->label('Try Out')->relationship('tryout', 'title'),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
