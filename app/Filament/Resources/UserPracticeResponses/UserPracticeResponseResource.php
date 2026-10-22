<?php

namespace App\Filament\Resources\UserPracticeResponses;

use App\Filament\Resources\UserPracticeResponses\Pages\ListUserPracticeResponses;
use App\Filament\Resources\UserPracticeResponses\Tables\UserPracticeResponsesTable;
use App\Models\UserPracticeResponse;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

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

    public static function table(Table $table): Table
    {
        return UserPracticeResponsesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUserPracticeResponses::route('/'),
        ];
    }
}
