<?php

namespace App\Filament\Resources\TryoutAttempts;

use App\Filament\Resources\TryoutAttempts\Pages\ListTryoutAttempts;
use App\Filament\Resources\TryoutAttempts\Pages\ViewTryoutAttempt;
use App\Filament\Resources\TryoutAttempts\Schemas\TryoutAttemptInfolist;
use App\Filament\Resources\TryoutAttempts\Tables\TryoutAttemptsTable;
use App\Models\TryoutAttempt;
use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Read-only Filament resource for TryoutAttempt at /admin/tryout-attempts:
 * mentee, Try Out, mode, status and bands, plus the detail page with every
 * answer, essay, Speaking transcript and AI assessment. Admins can re-run
 * a failed Writing/Speaking assessment and close a stuck attempt; answers are never edited.
 */
class TryoutAttemptResource extends Resource
{
    protected static ?string $model = TryoutAttempt::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Try Out';

    protected static ?string $navigationLabel = 'Hasil Mentee';

    protected static ?string $modelLabel = 'attempt';

    protected static ?string $pluralModelLabel = 'Hasil Try Out Mentee';

    protected static ?int $navigationSort = 4;

    /**
     * Build the detail view.
     *
     * @param  Schema  $schema  The schema provided by Filament.
     * @return Schema The configured schema.
     */
    public static function infolist(Schema $schema): Schema
    {
        return TryoutAttemptInfolist::configure($schema);
    }

    /**
     * Build the list table.
     *
     * @param  Table  $table  The table provided by Filament.
     * @return Table The configured table.
     */
    public static function table(Table $table): Table
    {
        return TryoutAttemptsTable::configure($table);
    }

    /**
     * Attempts are created by mentees only.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * Get the pages (routes) of this resource.
     *
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListTryoutAttempts::route('/'),
            'view' => ViewTryoutAttempt::route('/{record}'),
        ];
    }
}
