<?php

namespace App\Filament\Resources\TryoutAttempts\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Read-only detail of one Try Out attempt: summary and every subtest with its
 * answers (key, correctness, essays, recordings, transcripts, AI criteria).
 */
class TryoutAttemptInfolist
{
    /**
     * Add the entries to the schema.
     *
     * @param  Schema  $schema  The schema to configure.
     * @return Schema The configured schema.
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Attempt')
                    ->schema([
                        TextEntry::make('user.name')->label('Mentee'),
                        TextEntry::make('user.email')->label('Email')->copyable(),
                        TextEntry::make('tryout.title')->label('Try Out'),
                        TextEntry::make('mode')->label('Mode')->formatStateUsing(fn ($state): string => $state->getLabel()),
                        TextEntry::make('status')->label('Status')->badge(),
                        TextEntry::make('overall_band')->label('Band overall')->placeholder('-'),
                        TextEntry::make('started_at')->label('Mulai')->dateTime('j M Y H:i', 'Asia/Jakarta'),
                        TextEntry::make('completed_at')->label('Selesai')->dateTime('j M Y H:i', 'Asia/Jakarta')->placeholder('-'),
                    ])
                    ->columns(4)
                    ->columnSpanFull(),
                ViewEntry::make('detail')
                    ->view('filament.tryout.attempt-detail')
                    ->columnSpanFull(),
            ]);
    }
}
