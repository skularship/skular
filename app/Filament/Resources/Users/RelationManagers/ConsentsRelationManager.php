<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Enums\ConsentSource;
use App\Enums\LegalDocument;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Read-only consent history of a mentee (user_consents): which legal document
 * version they accepted, when, and where. No create, edit or delete actions:
 * the log is written only by ConsentService.
 */
class ConsentsRelationManager extends RelationManager
{
    protected static string $relationship = 'consents';

    protected static ?string $title = 'Riwayat persetujuan';

    protected static ?string $modelLabel = 'persetujuan';

    protected static ?string $pluralModelLabel = 'Riwayat persetujuan';

    /**
     * The history is visible on the edit page, but never editable.
     */
    public function isReadOnly(): bool
    {
        return true;
    }

    /**
     * Build the read-only consent table, newest first.
     *
     * @param  Table  $table  The table provided by Filament.
     * @return Table The configured table.
     */
    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('accepted_at', 'desc')
            ->columns([
                TextColumn::make('document')
                    ->label('Dokumen')
                    ->formatStateUsing(fn (LegalDocument $state): string => $state->label()),
                TextColumn::make('version')
                    ->label('Versi'),
                TextColumn::make('source')
                    ->label('Sumber')
                    ->formatStateUsing(fn (ConsentSource $state): string => $state->label()),
                IconColumn::make('age_confirmed')
                    ->label('Pernyataan usia')
                    ->boolean(),
                TextColumn::make('accepted_at')
                    ->label('Waktu')
                    ->dateTime('j M Y H:i')
                    ->sortable(),
            ]);
    }
}
