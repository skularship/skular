<?php

namespace App\Filament\Resources\PracticeForms;

use App\Filament\Resources\PracticeForms\Pages\CreatePracticeForm;
use App\Filament\Resources\PracticeForms\Pages\EditPracticeForm;
use App\Filament\Resources\PracticeForms\Pages\ListPracticeForms;
use App\Filament\Resources\PracticeForms\Schemas\PracticeFormForm;
use App\Filament\Resources\PracticeForms\Tables\PracticeFormsTable;
use App\Models\PracticeForm;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PracticeFormResource extends Resource
{
    protected static ?string $model = PracticeForm::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Materi & Latihan';

    protected static ?string $navigationLabel = 'Latihan praktik';

    protected static ?string $modelLabel = 'latihan praktik';

    protected static ?string $pluralModelLabel = 'Latihan praktik';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return PracticeFormForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PracticeFormsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPracticeForms::route('/'),
            'create' => CreatePracticeForm::route('/create'),
            'edit' => EditPracticeForm::route('/{record}/edit'),
        ];
    }
}
