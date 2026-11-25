<?php

namespace App\Filament\Resources\TryoutQuestions;

use App\Filament\Resources\TryoutQuestions\Pages\CreateTryoutQuestion;
use App\Filament\Resources\TryoutQuestions\Pages\EditTryoutQuestion;
use App\Filament\Resources\TryoutQuestions\Pages\ListTryoutQuestions;
use App\Filament\Resources\TryoutQuestions\Schemas\TryoutQuestionForm;
use App\Filament\Resources\TryoutQuestions\Tables\TryoutQuestionsTable;
use App\Models\TryoutQuestion;
use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Filament resource for TryoutQuestion at /admin/tryout-questions ("Bank Soal"):
 * every question type of the Try Out, JSON import (with dry run) and export,
 * duplicate, and archive instead of delete once mentees answered.
 */
class TryoutQuestionResource extends Resource
{
    protected static ?string $model = TryoutQuestion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static string|UnitEnum|null $navigationGroup = 'Try Out';

    protected static ?string $navigationLabel = 'Bank Soal';

    protected static ?string $modelLabel = 'soal';

    protected static ?string $pluralModelLabel = 'Bank Soal';

    protected static ?string $recordTitleAttribute = 'code';

    protected static ?int $navigationSort = 3;

    /**
     * Build the create/edit form.
     *
     * @param  Schema  $schema  The schema provided by Filament.
     * @return Schema The configured form schema.
     */
    public static function form(Schema $schema): Schema
    {
        return TryoutQuestionForm::configure($schema);
    }

    /**
     * Build the list table.
     *
     * @param  Table  $table  The table provided by Filament.
     * @return Table The configured table.
     */
    public static function table(Table $table): Table
    {
        return TryoutQuestionsTable::configure($table);
    }

    /**
     * Get the pages (routes) of this resource.
     *
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListTryoutQuestions::route('/'),
            'create' => CreateTryoutQuestion::route('/create'),
            'edit' => EditTryoutQuestion::route('/{record}/edit'),
        ];
    }
}
