<?php

namespace App\Filament\Resources\UserPracticeResponses;

use App\Filament\Resources\UserPracticeResponses\Pages\ListUserPracticeResponses;
use App\Filament\Resources\UserPracticeResponses\Tables\UserPracticeResponsesTable;
use App\Models\UserPracticeResponse;
use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Filament resource for UserPracticeResponse ("Jawaban latihan") at /admin/user-practice-responses.
 *
 * Read-only view of mentee practice answers, AI status and numeric score (admins only; mentees see labels).
 * Access is governed by the matching Shield policy in app/Policies.
 */
class UserPracticeResponseResource extends Resource
{
    protected static ?string $model = UserPracticeResponse::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Pemantauan Mentee';

    protected static ?string $navigationLabel = 'Jawaban latihan';

    protected static ?string $modelLabel = 'jawaban praktik';

    protected static ?string $pluralModelLabel = 'Jawaban latihan';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'id';

    /**
     * Build the list table.
     *
     * @param  Table  $table  The table provided by Filament.
     * @return Table The configured table.
     */
    public static function table(Table $table): Table
    {
        return UserPracticeResponsesTable::configure($table);
    }

    /**
     * Get the pages (routes) of this resource.
     *
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListUserPracticeResponses::route('/'),
        ];
    }
}
