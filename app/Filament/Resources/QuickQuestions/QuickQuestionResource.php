<?php

namespace App\Filament\Resources\QuickQuestions;

use App\Filament\Resources\QuickQuestions\Pages\CreateQuickQuestion;
use App\Filament\Resources\QuickQuestions\Pages\EditQuickQuestion;
use App\Filament\Resources\QuickQuestions\Pages\ListQuickQuestions;
use App\Filament\Resources\QuickQuestions\Schemas\QuickQuestionForm;
use App\Filament\Resources\QuickQuestions\Tables\QuickQuestionsTable;
use App\Models\QuickQuestion;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class QuickQuestionResource extends Resource
{
    protected static ?string $model = QuickQuestion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static string|UnitEnum|null $navigationGroup = 'Materi & Latihan';

    protected static ?string $navigationLabel = 'Quick Question';

    protected static ?string $modelLabel = 'quick question';

    protected static ?string $pluralModelLabel = 'Quick Question';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'question';

    public static function form(Schema $schema): Schema
    {
        return QuickQuestionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return QuickQuestionsTable::configure($table);
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
            'index' => ListQuickQuestions::route('/'),
            'create' => CreateQuickQuestion::route('/create'),
            'edit' => EditQuickQuestion::route('/{record}/edit'),
        ];
    }
}
